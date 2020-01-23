<?php

namespace Wf\Bundle\CmsBaseBundle\Manager;

use Doctrine\ORM\EntityManager;
use JMS\Serializer\NavigatorContext;
use JMS\Serializer\GraphNavigator;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Exception\InactiveScopeException;
use Wf\Bundle\CmsBaseBundle\Entity\Repository\PageVersionRepository;
use Wf\Bundle\CmsBaseBundle\Entity\Collection\PageEditorModuleCollectionFactory;
use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Wf\Bundle\CmsBaseBundle\Entity\PageVersion;
use Wf\Bundle\CmsBaseBundle\Entity\Article;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use JMS\Serializer\SerializerInterface;
use Metadata\Driver\DriverInterface;
use Metadata\MetadataFactoryInterface;
use Wf\Bundle\CommonBundle\Util\ClassUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PageManager
{
    /**
     * @var PageVersion
     */
    static protected $activeVersion = null;
    /**
     *
     * @var integer|null
     */
    static private $requestedVersion = null;

    /**
     * This service is (also) used in doctrine listeners,
     * we can't inject the repositories directly, as this would create
     * a circular dependency
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Used when instanciating new page classes
     * @var PageEditorModuleCollectionFactory
     */
    protected $pageEditorModuleCollectionFactory;

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * @var PageVersionRepository
     */
    protected $pageVersionRepository;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    protected $serializerMetadataFactory;

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        /*
         * store page version if requested
         * First store main request as getting the version already creates a circular dependency
         * Next @see self::getActivatedPageVersion
         */
        try {
            $request = $this->container->get('request');
        } catch (InvalidArgumentException $e) {
            return;
        } catch (ServiceNotFoundException $e) {
            return;
        } catch(InactiveScopeException $e) {
            return;
        }

        if (!self::$requestedVersion) {//only store once (aka the main request)
            self::$requestedVersion = $request->get('version', -1);//-1 so that only the main requests gets to set this static var
        }
    }

    public function setPageEditorModuleCollectionFactory($pageEditorModuleCollectionFactory)
    {
        $this->pageEditorModuleCollectionFactory = $pageEditorModuleCollectionFactory;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        if (is_null($this->entityManager)) {
            $this->entityManager = $this->container->get('doctrine')->getManager();
        }

        return $this->entityManager;
    }

    /**
     * @return PageRepository
     */
    public function getPageRepository()
    {
        if (is_null($this->pageRepository)) {
            $this->pageRepository = $this->container->get('wf_cms.repository.page');
        }

        return $this->pageRepository;
    }

    public function getRepositoryForEntity(Page $page)
    {
        return $this->getEntityManager()->getRepository(get_class($page));
    }

    /**
     * @return PageVersionRepository
     */
    public function getPageVersionRepository()
    {
        if (is_null($this->pageVersionRepository)) {
            $this->pageVersionRepository = $this->container->get('wf_cms.repository.page_version');
        }

        return $this->pageVersionRepository;
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function setSerializerMetadataFactory(MetadataFactoryInterface $metadataFactory)
    {
        $this->serializerMetadataFactory = $metadataFactory;
    }

    public function getNewPageVersion()
    {
        $className = $this->getPageVersionRepository()->getClassName();

        $pageVersion = new $className;
        $this->setupPageVersionEntity($pageVersion);

        return $pageVersion;
    }

    public function getNewPageArticle()
    {
        return $this->getNewPage(Page::TYPE_ARTICLE);
    }

    public function getNewPage($pageType = Page::TYPE_ARTICLE)
    {
        $pageClass = $this->getPageRepository()->getClassByPageType($pageType);

        $page = new $pageClass();
        $this->setupPageEntity($page);

        return $page;
    }

    public function getPageMetadata($allowedModules, $newModules)
    {
        $metadataClass = $this->container->getParameter('wf_cms.entity.page_metadata.class');
        $metadata = new $metadataClass();
        $metadata->setAllowedModules($allowedModules);
        $metadata->setNewModules($newModules);

        $metadataRepository = $this->container->get('wf_cms.repository.page_metadata');
        $dbMetadata = $metadataRepository->findOneByChecksum($metadata->getUpdateChecksum());
        if ($dbMetadata) {
            $metadata = $dbMetadata;
        }

        return $metadata;
    }

    public function savePage($page)
    {
        $em = $this->getEntityManager();
        $em->persist($page);
        //first, flush the page in order to get the sluggable listener to set its slug
        $em->flush();

        $em->refresh($page);//does this bring the actual slug back?! (if the slug was already existing, the pageVersion page was keeping the existing slug, not the ...-1 version)

        $versions = $this->getPageVersions($page);

        if (empty($versions)) {
            $version = $this->getNewPageVersion();
            $version->setPage($page);
            $em->persist($version);
            $em->flush();
        }

    }

    /**
     * @param Page $page
     */
    public function ensurePageSlug(Page $page, $slug)
    {
        $pageSlug = $this->container->get('wf_cms.repository.page_slug')->findOneBy(array(
                'slug' => $slug,
                'page' => $page,
            ));
        $pageSlugClass = $this->container->getParameter('wf_cms.entity.page_slug.class');
        if (!$pageSlug) {
            $pageSlug = new $pageSlugClass;
            $pageSlug->setSlug($slug);
            $pageSlug->setPage($page);
            $em = $this->getEntityManager();
            $em->persist($pageSlug);
            $em->flush();
        }
    }

    /**
     * Inject the required dependencies in the page object
     * @param Page $page
     */
    public function setupPageEntity(Page $page)
    {
        $page->setPageEditorModuleCollectionFactory($this->pageEditorModuleCollectionFactory);
    }

    /**
     * Inject the required dependencies in the page version object
     * @param PageVersion $pageVersion
     */
    public function setupPageVersionEntity(PageVersion $pageVersion)
    {
        $pageVersion->setSerializer($this->serializer);
        $pageVersion->setPageManager($this);
        $page = $pageVersion->getPageData();

        if (!empty($page)) {
            $this->setupPageEntity($page);
        }
    }

    public function cloneVersion($versionId)
    {
        $pageVersion = $this->getPageVersionRepository()->find($versionId);

        if (empty($pageVersion)) {
            throw new EntityNotFoundException(sprintf('PageVersion %d does not exist', $versionId));
        }

        $page = $pageVersion->getPage();
        $this->setupPageEntity($pageVersion->getPageData());
        $latestPageVersion = $this->getPageVersionRepository()->getLatestVersion($page);

        $newPageVersion = clone $pageVersion;
        $newPageVersion->setVersionNo($latestPageVersion->getVersionNo() + 1);
        $this->setupPageVersionEntity($newPageVersion);

        $pageVersion->freeze();

        return $newPageVersion;
    }

    public function updatePagePublishedAt($page)
    {
        if (is_int($page) && $page > 0) {
            $page = $this->getPageRepository()->find($page);
        }

        //see if there's a version already published
        $qb = $this->getPageVersionRepository()->getPublishedQB($page);
        $qb->andWhere('pv.publishedAt <= :publishedAt')
            ->setParameter('publishedAt', new \DateTime())
            ->orderBy('pv.publishedAt', 'DESC')
            ->addOrderBy('pv.createdAt', 'DESC')
            ->setMaxResults(1)
            ;
        $alreadyPublishedVersion = $qb->getQuery()->getOneOrNullResult();

        $shouldUpdate = false;
        if (!empty($alreadyPublishedVersion)) {
            $currentVersion = $page->getCurrentVersion();
            $pagePublishedAt = $page->getPublishedAt();
            $pagePublishedAt = $pagePublishedAt instanceof \DateTime ? $pagePublishedAt->format('YmdHis') : $pagePublishedAt;
            $alreadyPublishedAt = $alreadyPublishedVersion->getPublishedAt()->format('YmdHis');
            if (empty($currentVersion)
                 || (!empty($currentVersion) && $currentVersion->getId() != $alreadyPublishedVersion->getId())) {
                $this->setActiveVersion($alreadyPublishedVersion, false);

                $page->setPublishedAt($alreadyPublishedVersion->getPublishedAt());
                $shouldUpdate = true;

                /*
                var_dump(sprintf('page %d (version id %d) active published version id %d, date %s',
                    $page->getId(),
                    $currentVersion ? $currentVersion->getId() : 'none',
                    $alreadyPublishedVersion->getId(),
                    $alreadyPublishedVersion->getPublishedAt()->format('Y-m-d H:i:s')));
                    */
            }
            //initial page had no pageVersion Object, but now it does. set it
            if (empty($currentVersion) && $pagePublishedAt == $alreadyPublishedAt) {
                $page->setCurrentVersion($alreadyPublishedVersion);
                $shouldUpdate = true;
            }
        } else {
            $page->setCurrentVersion(null);
            $page->setPublishedAt(null);
        }

        //get next published version
        $qb = $this->getPageVersionRepository()->getPublishedQB($page);
        $qb->andWhere('pv.publishedAt > :publishedAt')
            ->setParameter('publishedAt', new \DateTime())
            ->orderBy('pv.publishedAt', 'ASC')
            ->addOrderBy('pv.createdAt', 'DESC')
            ->setMaxResults(1)
            ;
        $nextPublishedVersion = $qb->getQuery()->getOneOrNullResult();

        if (!empty($nextPublishedVersion)) {
            $nextVersion = $page->getNextVersion();
            if (empty($nextVersion) || $nextVersion->getId() != $nextPublishedVersion->getId()) {
                $page->setNextPublishedAt($nextPublishedVersion->getPublishedAt());
                $page->setNextVersion($nextPublishedVersion);
                $shouldUpdate = true;

                /* 
                $currentPublishedAt = $page->getPublishedAt();
                if (empty($currentPublishedAt)) {
                    //this page is still published, but in the future
                    $page->setPublishedAt($nextPublishedVersion->getPublishedAt());
                }
                 */

                /*
                $currentTime = new \DateTime();
                var_dump(sprintf('page %d (next stored version %d) next published version id %d, date %s, current %s',
                    $page->getId(),
                    $nextVersion ? $nextVersion->getId() : 'none',
                    $nextPublishedVersion->getId(),
                    $nextPublishedVersion->getPublishedAt()->format('Y-m-d H:i:s'),
                    $currentTime->format('Y-m-d H:i:s')));
                    */
            }
        } else {
            $page->setNextVersion(null);
            $page->setNextPublishedAt(null);
        }

        if ($shouldUpdate) {
            $this->getEntityManager()->persist($page);
            $this->getEntityManager()->flush();
        }

        return $shouldUpdate;
    }

    public function setActiveVersion(PageVersion $pageVersion, $shouldFlush = true)
    {
        $currentPage = $pageVersion->getPage();
        $this->getEntityManager()->refresh($currentPage);

        $newPage = $pageVersion->getPageData();
        $this->refreshPage($currentPage, $newPage);

        $currentPage->setCurrentVersion($pageVersion);
        if ($shouldFlush) {
            $this->getEntityManager()->persist($currentPage);
            $this->getEntityManager()->flush($currentPage);
        }

        return $currentPage;
        //$this->container->get('wf_cms.frontend_cache.manager')->purgePageCache($currentPage);
    }

    protected function refreshValue($value)
    {
        if (is_object($value) && strpos(get_class($value), 'Entity')) {
            $value = $this->getEntityManager()->getRepository(get_class($value))->find($value->getId());
        }

        if (is_array($value) || $value instanceof \Traversable) {
            foreach($value as $k=>$v) {
                $value[$k] = $this->refreshValue($v);
            }
        }

        return $value;
    }

    public function getVersionPageData(PageVersion $pageVersion)
    {
        $page = $pageVersion->getPageData();
        $this->refreshPage($page, $page);

        return $page;
    }

    public function refreshPage($page, $values)
    {
        $metadata = $this->serializerMetadataFactory->getMetadataForClass(get_class($page));
        $exclusionStrategy = new GroupsExclusionStrategy(array('version'));
        $navigatorContext = new NavigatorContext(GraphNavigator::DIRECTION_SERIALIZATION, 'xml');

        $skipProperties = array('article',
            'createdAt',//this should never be updated
            'images', 'videos', 'audios'//setting these to the page would add them again in the PEMC
        );
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($metadata->propertyMetadata as $propertyName=>$propertyMetadata) {
            if (null !== $exclusionStrategy && $exclusionStrategy->shouldSkipProperty($propertyMetadata, $navigatorContext)) {
                continue;
            }

            if (in_array($propertyName, $skipProperties)) {
                continue;
            }

            $value = $accessor->getValue($values, $propertyName);

            if (!is_null($value)) {
                $value = $this->refreshValue($value);
            }

            $accessor->setValue($page, $propertyName, $value);
        }
    }

    public function getPublishedBy($field, $value)
    {
        $page = $this->getPageRepository()->findOneBy(array($field => $value));
        if (!$page) {
            return null;
        }

        $publishedAt = $page->getPublishedAt();
        $nextPublishedAt = $page->getNextPublishedAt();
        $now = new \DateTime();

        if (!empty($nextPublishedAt) && $nextPublishedAt <= $now) {
            $this->updatePagePublishedAt($page);
            /*
            $currentTime = new \DateTime();
            var_dump(sprintf('time for next version, page %d, next version scheduled for publish at %s, current %s',
                $page->getId(),
                $nextPublishedAt->format('Y-m-d H:i:s'),
                $currentTime->format('Y-m-d H:i:s')
                ));
            */

            return $this->getPublishedBy($field, $value);
        }

        if (empty($publishedAt) || $publishedAt >= $now) {
            return null;
        }

        return $page;
    }

    public function getPublished($pageId)
    {
        return $this->getPublishedBy('id', $pageId);
    }

    public function isPublished(Page $page)
    {
        if ($page->getTypeName() == Page::TYPE_LISTING) {
            return $page;
        }
        $publishedAt = $page->getPublishedAt();
        $nextPublishedAt = $page->getNextPublishedAt();
        $now = new \DateTime();

        if (!empty($nextPublishedAt) && $nextPublishedAt <= $now) {
            $this->updatePagePublishedAt($page);

            //reselect it from DB
            return $this->isPublished($page);
        }

        if (empty($publishedAt) || $publishedAt >= $now) {
            return null;
        }

        return $page;
    }

    public function getPageVersions(Page $page, $limit = 5)
    {
        $pageVersions = $this->getPageVersionRepository()->getVersions($page, $limit);
        foreach($pageVersions as $pageVersion) {
            $this->setupPageVersionEntity($pageVersion);
        }
        return $pageVersions;
    }

    public function getPageVersionsById($pageId, $limit = 5)
    {
        $pageRepository = $this->getPageRepository();
        $page = $pageRepository->find($pageId);
        if (!$page) {
            return null;
        }

        return $this->getPageVersions($page, $limit);
    }

    public function getPageTypes()
    {
        return $this->getPageRepository()->getPageTypes();
    }

    /**
     * extracts domain from an url and returns the domain as a hash key
     * should return the same value as the js function in the see section bellow
     * @see Wf\Bundle\TrackingBundle\Resources/node_app/node_server.js@getTrackingDomainKey
     * @param string $url
     * @return string
     */
    private function getTrackingDomainKey($host)
    {
        $domain = preg_replace('/^.*?(www\.)?([^\/]+)/', '$2', $host);
        $domainKey = str_replace('.', '_', $domain);
        return $domainKey;

    }

    private function getTrackingContext($context)
    {
        try {
            /* @var $request \Symfony\Component\HttpFoundation\Request */
            $request = $this->container->get('request');
            if ($request) {
                $domains = (array)$this->container->getParameter('cms_category_domains');
                $domains = array_values($domains);
                $domain = $request->getHost();
                $domainKey = $this->getTrackingDomainKey($domain);
                $foundDomain = false;
                foreach($domains as $categoryDomain) {
                    if ($this->getTrackingDomainKey($categoryDomain) == $domainKey) {
                        $foundDomain = true;
                        break;
                    }
                }
                if ($foundDomain) {
                    $context = $domainKey . '_' . $context;
                }
            }
        } catch (\Exception $e) {
        }

        return $context;
    }

    /**
     * @param $context - 'pageviews' or 'comments'
     * @param $start - index to start, default is first
     * @param $end - index to end, default is last available
     */
    public function getTrackingPages($context = 'pageviews', $start = 0, $end = -1)
    {
        $context = $this->getTrackingContext($context);

        $stats = $this->container
            ->get('wf_tracking.repository')
            ->getMost('trk:'.$context, $start, $end);

        return $this->getPagesFromTrackingStats($stats);
    }

    public function getCategoryTrackingPages($category, $context = 'pageviews', $start = 0, $end = -1)
    {
        $context = $this->getTrackingContext($context);
        $stats = $this->container
            ->get('wf_tracking.repository')
            ->getCategoryMost($category->getId(), 'trk:'.$context, $start, $end);

        return $this->getPagesFromTrackingStats($stats);
    }

    private function getPagesFromTrackingStats($stats)
    {
        $pageIds = array();

        $scores = array();
        // get page id from each statistic
        foreach ($stats as $id => $score) {
            $scores[$id] = $score;
            $pageIds[] = $id;
        }

        if(empty($pageIds)) {
            return array();
        }

        $results = $this
            ->getPageRepository()
            ->getBaseQB()
            ->byIds($pageIds)
            ->getResults();

        // order pages by initial ids
        $pages = array();
        foreach ($results as $page) {
            $pages[$page->getId()] = $page;
        }

        $ordered = array();
        foreach($pageIds as $pageId) {
            if(array_key_exists($pageId, $pages)) {
                $ordered[$pageId] = $pages[$pageId];
                $pages[$pageId]->setPageViews($scores[$pageId]);
                unset($pages[$pageId]);
            }
        }

        return $ordered;
    }


    public function getTrackingPagesCount($context = 'pageviews')
    {
        return $this->container
                    ->get('wf_tracking.repository')
                    ->getMostCount('trk:' . $context);
    }

    public function getPagesNotPublishedAt(array $pagesIds, \DateTime $publishedAt)
    {
        $publishedPages = array();
        $targetPages = array();
        $allTargetPages = $this->getPageRepository()->findByIds($pagesIds);
        foreach($allTargetPages as $targetPage) {
            if (!$targetPage->isPublished()) {
                $targetPages[] = $targetPage;//mark to look for a published version
            } else {
                //check if page is published now or in the furture
                if ($targetPage->getPublishedAt() > $publishedAt) {
                    $targetPages[] = $targetPage->getId();//mark to look for a published version
                } else {
                    $publishedPages[] = $targetPage->getId();//page is published
                }
            }
        }

        if (!empty($targetPages)) {
            $targetVersions = $this->getPageVersionRepository()->getByPagesBeforePublishedAt($targetPages, $publishedAt);

            foreach($targetVersions as $pageVersion) {
                $publishedPages[] = $pageVersion->getPage()->getId();//a version is published
            }
        }

        sort($pagesIds);
        sort($publishedPages);

        if (array_intersect($publishedPages, $pagesIds) == $pagesIds) {//all related pages are published
            return false;
        }

        //list of unpublished pages
        $notPublished = array_diff($pagesIds, $publishedPages);
        $notPublishedPages = array();
        foreach($notPublished as $notPublishedPageId) {
            foreach($targetPages as $page) {
                if ($page->getId() == $notPublishedPageId) {
                    $notPublishedPages[] = $page;
                    break;
                }
            }
        }

        return $notPublishedPages;
    }

    public function getActivatedPageVersion()
    {
        if (!self::$activeVersion && self::$requestedVersion && self::$requestedVersion > 0) {
            $version = self::$requestedVersion;
            if ($version) {
                $pageVersionRepository = $this->getPageVersionRepository();
                $pageVersion = $pageVersionRepository->find($version);
                if ($pageVersion) {
                    $this->setActivatedPageVersion($pageVersion);
                }
            }
        }
        return self::$activeVersion;
    }

    public function setActivatedPageVersion(PageVersion $pageVersion)
    {
        if (!self::$activeVersion) {
            self::$activeVersion = $pageVersion;
            $page = $pageVersion->getPageData();
            $auth = $page->getAuthor();
            $auth = $this->refreshValue($auth);
        }
    }

    public function unpublishAllVersions(Page $page)
    {
        if ($page->getVersion()) {//a versioned page; we need the actual page
            $page = $page->getVersion()->getPage();
        }

        $pageVersionRepository = $this->getPageVersionRepository();
        $em = $this->getEntityManager();
        $allVersions = $pageVersionRepository->findByPage($page);
        foreach($allVersions as $pageVersion) {
            if ($pageVersion->getPublishedAt()) {
                $this->refreshSitemap($pageVersion, true);
                $pageVersion->unpublish();
                $em->persist($pageVersion);
            }
        }

        $this->refreshSitemap($page, true);
        $page->unpublish();
        $em->persist($page);
        $em->flush();
    }

    /**
     * remove a cached sitemap file and ping google
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Page|\Wf\Bundle\CmsBaseBundle\Entity\PageVersion $page
     * @param boolean $resubmit - whether to ping google's webmaster tool about the sitemap change
     *                          - should be true when unpublishing/deleting
     */
    public function refreshSitemap($page, $resubmit = false)
    {
        $router = $this->container->get('router');

        //remove sitemap file
        $now = new \DateTime();
        if (!$page->getPublishedAt()) {
            return;
        }
        $pageMonth = $page->getPublishedAt()->format('Y-m');
        if ($pageMonth == $now->format('Y-m')) {
            $pageMonth = 'current';
        }

        if (!method_exists($page, 'getCategory')) {
            return;
        }

        $category = $page->getCategory();

        if (!$category) {
            return;
        }

        while($category->getParent()) {
            $category = $category->getParent();
        }
        $sitemapFile = $router->generate('PrestaSitemapBundle_section', array('name' => 'articles-' . $pageMonth, '_format' => 'xml'));
        $this->refreshSitemapFile($sitemapFile, $category ? $category->getSlug() : null, $resubmit);
        $sitemapFile = $router->generate('PrestaSitemapBundle_section', array('name' => 'tags', '_format' => 'xml'));
        $this->refreshSitemapFile($sitemapFile, $category ? $category->getSlug() : null, $resubmit);
    }

    protected function refreshSitemapFile($sitemapFile, $category = null, $resubmit = false)
    {
        $dir = $this->container->getParameter('wf_cms_sitemap_path');
        $debug = $this->container->getParameter('kernel.debug');
        $filename = str_replace('sitemap-', '', basename($sitemapFile));

        $categoryDomains = $this->container->getParameter('cms_category_domains');
        if (!isset($categoryDomains[$category])) {
            $category = null;
        }

        $path = $dir . DIRECTORY_SEPARATOR . ($category ? $category . DIRECTORY_SEPARATOR : '') . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path) && is_writable($path)) {
            unlink($path);
            if (!$debug && $resubmit) {
                try {
                    $request = $this->container->get('request');
                    $sitemapUrl = $request->getSchemeAndHttpHost() . '/' . $sitemapFile;
                    //ping google
                    file('http://www.google.com/webmasters/tools/ping?sitemap=' . urlencode($sitemapUrl));
                } catch (\Exception $e){}
            }
        }
    }

    /**
     * get the version of a page
     * the returned page has embedded the respective PageVersion and the last $versionsLimit versions
     *
     * @param integer $pageId
     * @param integer $version
     * @param integer $versionsLimit
     * @return \Wf\Bundle\CmsBaseBundle\Entity\Page|null
     */
    public function getPageByVersion($pageId, $version = null, $versionsLimit = 5)
    {
        $pageVersion = null;
        $pageLastVersions = $this->getPageVersionsById($pageId, $versionsLimit);
        if (empty($version)) {
            if (!empty($pageLastVersions)) {
                $pageVersion = reset($pageLastVersions);
                $version  = $pageVersion->getId();
            } else {
                $page = $this->getPageRepository()->find($pageId);
                if (!$page) {
                    return null;
                }
            }
        } else {
            $pageVersion = $this->getPageVersionRepository()->find($version);
            if (!$pageVersion) {
                return null;
            }
        }

        if ($pageVersion) {
            $page = $pageVersion->getPageData();
            $page->setVersion($pageVersion);
        }
        $page->setLastVersions($pageLastVersions);

        return $page;
    }
}