<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Wf\Bundle\CmsBaseBundle\Entity\Category;
use Wf\Bundle\CmsBaseBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Wf\Bundle\CmsBaseBundle\Sitemap\SitemapCapableInterface;

/**
 * PageRepository
 */
class PageRepository extends EntityRepository
    implements SitemapCapableInterface, ContainerAwareInterface
{
    protected $qbClass = 'Wf\Bundle\CmsBaseBundle\Entity\Repository\PageQueryBuilder';
    protected $publishListManagerClass = 'Wf\Bundle\CmsBaseBundle\Publish\Manager\BaseManager';

    /**
     * @var PageSlugRepository
     */
    public $pageSlugsRepository;

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->pageSlugsRepository = $container->get('wf_cms.repository.page_slug');
    }


    /** Query Builder methods */

    /**
     * @return PageQueryBuilder $qb
     */
    public function getBaseQB($onlyActive = true)
    {
        $qb = new $this->qbClass($this->getEntityManager(), $this->getClassMetadata());
        $qb->onlyActive($onlyActive);

        return $qb;
    }

    public function getLatestQB(Category $category = null, $limit = 5, $onlyActive = false, $categoryFields = true, $categoryChildren = null)
    {
        $qb = $this->getBaseQB($onlyActive)
            ->byCategory($category, $categoryChildren, $categoryFields)
            ->groupById()
            ->limit($limit);

        $listName = null;
        if (!empty($category)) {
            $template = $this->getListTemplate('LATEST_CATEGORY');
            $listName = sprintf($template, $category->getId());
        } else {
            $listName = $this->getListTemplate('LATEST');
        }
        $qb->byList($listName);


        return $qb;
    }

    public function getLatestPublishedQb(\DateTime $startTime = null, Category $category = null, $limit = 5, $categoryFields = true, $categoryChildren = null)
    {
        $query = $this->getLatestQB($category, $limit, true, $categoryFields, $categoryChildren)
                    ->publishedAtStartingFrom($startTime);
        return $query;
    }

    public function getByAuthorQB(User $author, Category $category = null, $limit = 5)
    {
        $qb = $this->getLatestQB($category, $limit, true, false);

        $listName = null;
        if (!empty($category)) {
            $template = $this->getListTemplate('LATEST_AUTHOR_CATEGORY');
            $listName = sprintf($template, $author->getId(), $category->getId());
        } else {
            $template = $this->getListTemplate('LATEST_AUTHOR');
            $listName = sprintf($template, $author->getId());
        }

        $qb->byList($listName);

        return $qb;
    }

    public function getByEditionQB($edition)
    {
        return $this->getBaseQB()
            ->byEdition($edition);
    }

    public function getLatestByStatusQB($status, $limit = 10)
    {
        return $this->getBaseQB($onlyActive = false)
            ->byStatus($status)
            ->limit($limit);
    }

    public function findByPageTypeQB($pageType)
    {
        return $this->getBaseQB($onlyActive = false)
            ->byType($pageType);
    }

    public function getSearchableQB()
    {
        $qb =  $this->getBaseQB(false)
            ->byType(Page::TYPE_ARTICLE);

        $qb->orderBy($qb->rootAlias . '.publishedAt', 'DESC');
        return $qb;
    }

    public function findByIdsQB(array $pagesIds)
    {
        if (empty($pagesIds)) {
            return array();
        }
        return $this->getBaseQB(false)
                    ->byIds($pagesIds);
    }

    /**
     *
     * @return \Wf\Bundle\CmsBaseBundle\Entity\Repository\PageQueryBuilder
     */
    public function getPublishedQB()
    {
        $qb = $this->getBaseQB(true);
        $qb->byType(Page::TYPE_ARTICLE);

        $listName = $this->getListTemplate('LATEST');
        $qb->byList($listName);

        return $qb;
    }

    /** finder methods */

    /**
     * overload find by slug to look in page slug history if not found
     * @param string $slug
     * @return \Wf\Bundle\CmsBaseBundle\Entity\Page|null
     */
    public function findOneBySlug($slug)
    {
        $page = parent::findOneBySlug($slug);
        if ($page) {
            return $page;
        }
        if (!$this->pageSlugsRepository) {
            return null;
        }

        $pages = $this->pageSlugsRepository->findBy(array('slug' => $slug), array('id' => 'DESC'), 1);
        if (!empty($pages)) {
            $pageSlug = array_shift($pages);
            $page = $pageSlug->getPage();
            if ($page) {
                $page->needsRedirect(true);
                return $page;
            }
        }

        return null;
    }

    /** utility methods */

    public function getListTemplate($listTemplateName)
    {
        $constant = $this->publishListManagerClass . '::' . $listTemplateName;
        if (!defined($constant)) {
            throw new \InvalidArgumentException(sprintf('List %s is not defined', $listTemplateName));
        }

        return constant($constant);
    }

    public function getLastByCategory(Category $category)
    {
        $pages = $this->getLatest($category, 1, true);
        if ($pages) {
            return reset($pages);
        }

        return null;
    }

    public function getFirst()
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.id', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(1);
        $pages = $qb->getQuery()
            ->execute();

        if (!count($pages)) {
            return null;
        }

        return array_pop($pages);
    }

    public function getLatestPages() {
        return $this->getLatestQB(14);
    }

    public function getPageTypes()
    {
        $classMetadata = $this->getClassMetadata();

        $ret = array();

        foreach ($classMetadata->discriminatorMap as $pageType=>$pageClass) {
            $ret[] = $pageType;
        }

        return $ret;
    }

    public function getBoardTypes()
    {
        $ret = $this->getPageTypes();

        foreach ($ret as $k=>$pageType) {
            if ($pageType == Page::TYPE_ARTICLE) {
                unset($ret[$k]);
            }
        }

        return $ret;
    }

    public function getClassByPageType($pageType)
    {
        $classMetadata = $this->getClassMetadata();
        if (isset($classMetadata->discriminatorMap[$pageType])) {
            return $classMetadata->discriminatorMap[$pageType];
        }
    }

    public function getFormTypeByPageType($pageType)
    {
        $class = $this->getClassByPageType($pageType);
        return call_user_func_array(array($class, 'getFormType'), array());
    }

    public function typeExists($type)
    {
        $classMetadata = $this->getClassMetadata();
        return isset($classMetadata->discriminatorMap[$type]);
    }

    /** operation methods */

    public function deletePage($page) {
        $em = $this->getEntityManager();
        $em->remove($page);
        $em->flush();
        return true;
    }

    /** service methods */

    
    public function getSitemapList(\DateTime $month = null, $categories = null, $withTags = false)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id')
            ->addScalarResult('slug', 'slug')
            ->addScalarResult('title', 'title')
            ->addScalarResult('fresh', 'fresh')
            ->addScalarResult('published_at', 'published_at', 'datetime')
            ->addScalarResult('tags', 'tags');
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $sql = "SELECT  p.`id`, p.`slug`, p.`published_at`, p.title,
                        %s as `tags`,
                        %s as fresh
                FROM `" . $this->getClassMetadata()->table['name'] . "` p
                %s
                WHERE p.`published_at` IS NOT NULL AND p.`published_at` < '".$now. "'
                    AND p.`status`='" . Page::STATUS_PUBLISHED . "'
                    AND p.`page_type`='" . Page::TYPE_ARTICLE . "'
                    %s
                %s
                ORDER BY p.published_at DESC";

        $tags = "''";
        $join = '';
        if ($withTags) {
            $join = " LEFT JOIN `page_tag` pt
                        ON p.id = pt.page_id
                      LEFT JOIN `tag` t
                        ON pt.tag_id = t.id";
            $tags = "GROUP_CONCAT(t.title SEPARATOR ',')";
        }

        $fresh = '0';
        $where = '';
        if (!empty($month)) {
            $ym = $month->format('Y-m');
            $next = clone $month;
            $next->add(new \DateInterval('P1M'));
            $fresh = "IF(DATE_FORMAT(p.published_at, '%Y-%m')='{$ym}', 1, 0)";
            $where = " AND p.`published_at` >= '" . $month->format('Y-m-d H:i:s') . "'
                       AND p.`published_at` < '" . $next->format('Y-m-d H:i:s') . "'";
        }

        if (!empty($categories)) {
            $where .= ' AND p.`category_id` IN (' . implode(',', $categories) . ')';
        }

        $group = '';
        if ($withTags) {
            $group = ' GROUP BY pt.page_id';
        }
        $sql = sprintf($sql, $tags, $fresh, $join, $where, $group);
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        return $query;
    }

    /**
     * if the QB method exists, then call it and get query and results
     * if the method ends in PA then get QB and return new Adapter
     * @param string $method
     * @param array $arguments
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this, $method . 'QB')) {
            $qb = call_user_func_array(array($this, $method . 'QB'), $arguments);
            if ($qb instanceof $this->qbClass) {
                return $qb->getResults();
            }

            return $qb;
        }
        if (strtolower(substr($method, -5)) == 'query') {
            $method = substr($method, 0, strlen($method) - 5);
            if (method_exists($this, $method . 'QB')) {
                $qb = call_user_func_array(array($this, $method . 'QB'), $arguments);
                if ($qb instanceof $this->qbClass) {
                    $qb->setList(null);

                    return $qb;
                }

                return $qb;
            }
        }

        return parent::__call($method, $arguments);
    }
}