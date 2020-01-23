<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Wf\Bundle\CmsBaseBundle\Manager\DomainManager;
use Wf\Bundle\CmsBaseBundle\Entity\Category;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\NoResultException;

/**
 */
class CategoryController extends Controller
{
    /**
     * @param string $categorySlug
     * @Template()
     */
    public function showAction($categorySlug)
    {
        $categoryAction = $this->getInflectedName($categorySlug);
        if (method_exists($this, $categoryAction . 'Action')) {
            return $this->forward('WfCmsBaseBundle:Category:' . $categoryAction);
        }

        $category = $this->getCategoryBySlug($categorySlug);

        return $this->renderCategoryView($category);
    }

    /**
     * @param string $categorySlug
     * @param integer $page
     * @Template()
     */
    public function latestAction($categorySlug, $page = 1, $articlesPerPage = null)
    {
        $category = $this->getCategoryBySlug($categorySlug);
        $pagesQB = $this->getCategoryLatestQB($categorySlug);

        $pages = $this->getPagerFanta($pagesQB);

        if (!is_null($articlesPerPage)) {
           $pages->setMaxPerPage($articlesPerPage); 
        }

        $pages->setCurrentPage($page);

        $data = array(
            'pages' => $pages,
            'routeName' => 'wf_category_show',
            'routeParams' => array(
                'categorySlug' => $categorySlug
            ),
        );
        if (!is_null($articlesPerPage)) {
            $data['routeParams']['articlesPerPage'] = $articlesPerPage;
        }

        return $this->renderCategoryView($category, $data, 'latest');
    }
    
    /**
     * 
     * @param string $categorySlug
     * @param integer $articlesPerPage
     * @return array
     * @Template()
     */
    public function latestSidebarAction($categorySlug, $articlesPerPage = 10)
    {
        $category = $this->getCategoryBySlug($categorySlug);
        $pagesQB = $this->getCategoryLatestQB($categorySlug);
        $pagesQB->setMaxResults($articlesPerPage);
        
        return $this->renderCategoryView($category, array('pages' => $pagesQB->getResults()), 'latestSidebar');
    }

    /**
     *
     * @param string $categorySlug
     * @param integer $year
     * @param integer $month
     * @param integer $day
     * @param integer $page
     * @Template()
     */
    public function archiveAction($categorySlug, $year = 0, $month = 0, $day = 0)
    {
        $category = $this->getCategoryBySlug($categorySlug);

        $data = array();
        $data['year'] = (int)$year;
        $data['month'] = (int)$month;
        $data['day'] = (int)$day;

        return $this->renderCategoryView($category, $data, 'archive');
    }

    public function archiveListingAction($categorySlug, $year = 0, $month = 0, $day = 0, $page = 1)
    {
        $category = $this->getCategoryBySlug($categorySlug);
        $data = array();

        list($startDate, $endDate) = $this->getDateInterval($year, $month, $day);
        $data['routeName'] = 'wf_category_archive';
        $data['routeParams'] = array(
            'categorySlug' => $categorySlug,
            'year' => $year,
            'month' => $month,
            'day' => $day,
        );

        $terms = array(
            'category' => $categorySlug,
            'publishedAt' => array(
                'from' => $startDate,
                'until' => $endDate,
            )
        );

        $pages = $data['pages'] = $this->getArticleFinder()->findPaginatedByTerms($terms);
        $pages->setCurrentPage($page);

        return $this->renderCategoryView($category, $data, 'latest');
    }

    /**
     *
     * @param string $categorySlug
     * @param string $tagSlug
     * @param integer $page
     * @Template()
     */
    public function taggedAction($categorySlug, $tagSlug)
    {
        $category = $this->getCategoryBySlug($categorySlug);
        $tag = $this->getTagBySlug($tagSlug);
        if (empty($tag)) {
            throw $this->createNotFoundException();
        }

        $data = array(
            'categorySlug' => $tag,
            'tagSlug' => $tagSlug,
        );

        return $this->renderCategoryView($category, $data, 'tagged');
    }

    public function taggedListingAction($categorySlug, $tagSlug, $page = 1)
    {
        $category = $this->getCategoryBySlug($categorySlug);
        $tag = $this->getTagBySlug($tagSlug);
        if (empty($tag)) {
            throw $this->createNotFoundException();
        }
        $terms = array(
            'category' => $categorySlug,
            'tags' => array($tag->getTitle()),
        );

        $data = array();
        $data['tagSlug'] = $tagSlug;
        $data['routeName'] = 'wf_category_tagged';
        $data['routeParams'] = array(
            'categorySlug' => $categorySlug,
            'tagSlug' => $tagSlug,
        );

        $pages = $data['pages'] = $this->getArticleFinder()->findPaginatedByTerms($terms);
        $pages->setCurrentPage($page);

        return $this->renderCategoryView($category, $data, 'latest');
    }

    /**
     * @param string $categorySlug
     * @Template()
     */
    public function latestSubcategoryAction($categorySlug)
    {
        $limit = $this->container->getParameter('wf_cms.category.latest_subcategory_results');
        $pageRepository = $this->get('wf_cms.repository.page');
        $page = $pageRepository->findOneBySlug($categorySlug);

        if (!$page) {
            return new Response('');
        }

        $category = $page->getCategory();

        $pageRepository = $this->get('wf_cms.repository.page_article');

        if (!empty($category)) {
            $categoryChildren = $this->getCategoryChildren($category);
            $latestQb = $pageRepository->getLatestPublishedQb(null, $category, $limit, false, $categoryChildren);
            if (method_exists($page, 'getEmbeddedPages')) {
                $latestQb->excludeIds($page->getEmbeddedPages());
            }

            $ret['latest'] = $latestQb->getResults();
            $ret['category'] = $category;
            $ret['slug'] = $categorySlug;
        }

        return $ret;
    }

    /**
     * @Template()
     * @param string $pageSlug
     * @param string $direction
     */
    public function pageSiblingsAction($pageSlug)
    {
        $pageRepository = $this->get('wf_cms.repository.page_article');
        $page = $pageRepository->findOneBySlug($pageSlug);
        if (!$page) {
            return new Response('');
        }

        $publishedAt = $page->getPublishedAt() ? clone $page->getPublishedAt() : null;
        if (!$publishedAt) {
            return new Response('');
        }
        
        $next = $prev = null;
        $listManager = $this->get('wf_cms.publish.manager');
        $listTemplate = constant(get_class($listManager) . '::LATEST_CATEGORY');
        $listName = sprintf($listTemplate, $page->getCategory()->getId());
        $pageIndex = $listManager->getPageIndex($listName, $page);
        if (is_numeric($pageIndex)) {
            $ids = array();
            $beforePageId = $afterPageId = null;
            if ($pageIndex > 0) {
                $afterIndex = $pageIndex - 1;
                $listIds = $listManager->getListSlice($listName, $afterIndex, $afterIndex);
                $ids[] = $afterPageId = reset($listIds);
            }
            if ($pageIndex < $listManager->getListSize($listName) - 1) {
                $beforeIndex = $pageIndex + 1;
                $listIds = $listManager->getListSlice($listName, $beforeIndex, $beforeIndex);
                $ids[] = $beforePageId = reset($listIds);
            }
            if (!empty($ids)) {
                $qb = $pageRepository->getBaseQB();
                $qb->byIds(array($beforePageId, $afterPageId));
                $pagesAround = $qb->getResults();
                if (count($pagesAround) && $pagesAround[0]->getId() === $beforePageId) {
                    $prev = $pagesAround[0];
                } elseif (count($pagesAround) && $pagesAround[0]->getId() === $afterPageId) {
                    $next = $pagesAround[0];
                }
                if (count($pagesAround) > 1 && $pagesAround[1]->getId() == $beforePageId) {
                    $prev = $pagesAround[1];
                } elseif (count($pagesAround) > 1 && $pagesAround[1]->getId() == $afterPageId) {
                    $next = $pagesAround[1];
                }
            }
        }

        return array(
            'prev' => $prev,
            'next' => $next
        );
    }

    /**
     *
     * @param string $categorySlug
     * @param integer $year
     * @param integer $month
     * @param integer $day
     * @Template()
     */
    public function calendarWidgetAction($categorySlug, $year = 0, $month = 0, $day = 0)
    {
        if (!$year) {
            $endDate = date('Y/m/d');
        } else {
            list(, $endDate) = $this->getDateInterval($year, $month, $day);
        }

        $date = new \DateTime($endDate);
        $now = new \DateTime();
        if ($date > $now) {
            $endDate = $now->format('Y/m/d');
        }

        $articleFinder = $this->getArticleFinder();
        $histogram = $articleFinder->getCountByPublishInterval(array('category' => $categorySlug));

        $origin_dtz = new \DateTimeZone(date_default_timezone_get());
        $origin_dt = new \DateTime("now", $origin_dtz);
        $offset = $origin_dtz->getOffset($origin_dt);

        foreach($histogram as &$byMonthCount) {
            $byMonthCount['date'] = new \DateTime('@' . ($byMonthCount['time'] - $offset), new \DateTimeZone(date_default_timezone_get()));
        }

        return array(
            'categorySlug' => $categorySlug,
            'selectedDate' => $endDate,
            'histogram' => $histogram,
        );
    }

    /**
     * renders a category front page using template vars passed by the action and uses $view as the template
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Category $category
     * @param type $data
     * @param type $view
     * @param type $format
     * @return \Symfony\Component\HttpFoundation\Response|\Wf\Bundle\CmsBaseBundle\Entity\Category
     */
    protected function renderCategoryView(Category $category, $data = array(), $view = null, $format = 'html')
    {
        $request = $this->get('request');
        $data['page'] = $request->get('page', 1);

        $categoryDomains = $this->container->getParameter('cms_category_domains');
        $categorySlug = DomainManager::addPrefix($categoryDomains, $category->getSlug());

        $parentCategory = $category->getParent();
        $mainCategory = $category;

        $slug = $category->getSlug();
        $subcategorySlug = '';
        $bodyClass = 'section-' . $slug;
        if (!empty($parentCategory)) {
            $mainCategory = $parentCategory;
            $subcategorySlug = $slug;
            $slug = $parentCategory->getSlug();
            $bodyClass = 'section-' . $slug . ' ' . $bodyClass;
        }
        $bodyClass = preg_replace('@[^a-z- ]+@i', '-', $bodyClass);

        if (!$this->container->get('kernel')->isAdmin()) {
            $redirect = $this->manageDomainRedirects($slug, $subcategorySlug);
            if ($redirect instanceof Response) {
                return $redirect;
            }
        }

        $data['bodyClass'] = $bodyClass;
        $data['mainCategory'] = $mainCategory;
        $data['category'] = $category;

        $categoryTemplate = $category->getTemplate() 
                            ? $category->getTemplate() 
                            : ($parentCategory && $parentCategory->getTemplate() 
                                ? $category->getParent()->getTemplate() 
                            : null
                            );
        $extension = '.' . $format . '.twig';
        $suffix = '';
        if (!empty($view)) {
            $suffix .= '_' . $view;
        }
        $suffix .= $extension;

        if (!empty($categoryTemplate)) {
            //render category using it's template property
            $personalizedTemplateSpec = 'WfCmsBaseBundle:Category:' . $categoryTemplate . $suffix;
            if ($this->get('templating')->exists($personalizedTemplateSpec)) {
                $template = $personalizedTemplateSpec;
            }
        }

        if (empty($template)) {
            //render category by it's slug (for specific category personalization)
            $personalizedTemplateSpec = 'WfCmsBaseBundle:Category:' . $this->getInflectedName($categorySlug) . $suffix;
            if ($this->get('templating')->exists($personalizedTemplateSpec)) {
                $template = $personalizedTemplateSpec;
            }
        }
        
        if (empty($template)) {
            if (empty($view)) {
                $view = 'show';
            }
            $template = 'WfCmsBaseBundle:Category:' . $view . $extension;
        }

        return $this->render($template, $data);
    }
    
    public function render($view, array $parameters = array(), Response $response = null)
    {
        $response = parent::render($view, $parameters, $response);
        if (!empty($parameters['category']) && isset($parameters['mainCategory']) && $parameters['category'] instanceof Category) {
            $response->headers->add(array(
                'X-Cache-Category-Id' => $parameters['category']->getId(),
            ));
        }
        
        return $response;
    }

    protected function getDateInterval($year, $month, $day)
    {
        $months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
        $startDate = $endDate = null;
        $firstDay = 'first day of %s %d midnight';
        $lastDay = 'last day of %s %d';
        if (!empty($day)) {
            $month = $month ?: date('m');
            $startDate = $endDate = $year . '-' . sprintf('%02d', $month) . '-' . sprintf('%02d', $day);
        } else if (!empty($month)) {
            $monthName = $months[$month - 1];
            $startDate = date('Y-m-d', strtotime(sprintf($firstDay, $monthName, $year)));
            $endDate = date('Y-m-d', strtotime(sprintf($lastDay, $monthName, $year)));
        } else {
            $startMonth = reset($months);
            $endMonth = end($months);
            $startDate = date('Y-m-d', strtotime(sprintf($firstDay, $startMonth, $year)));
            $endDate = date('Y-m-d', strtotime(sprintf($lastDay, $endMonth, $year)));
        }

        return array($startDate, $endDate);
    }

    /**
     * @return Wf\Bundle\CmsBaseBundle\Search\Finder\ArticleFinder
     */
    protected function getArticleFinder()
    {
        return $this->get('wf_cms.search.article_finder');
    }

    /**
     * 
     * @param string $categorySlug
     * @return \Wf\Bundle\CmsBaseBundle\Entity\Repository\PageQueryBuilder
     */
    protected function getCategoryLatestQB($categorySlug)
    {
        $category = $this->getCategoryBySlug($categorySlug);
        $categoryChildren = $this->getCategoryChildren($category);

        $excludeIds = $this->getPageExcludeIds($categorySlug);

        $pageRepository = $this->get('wf_cms.repository.page_article');
        $pagesQB = $pageRepository->getLatestQB($category, $limit = null, $onlyActive = true, $categoryFields = true, $categoryChildren);
        $pagesQB->excludeIds($excludeIds);

        return $pagesQB;
    }

    protected function getPagerFanta($qb)
    {
        $pagerFactory = $this->get('wf_cms.pager.factory');
        $pf = $pagerFactory->createPager($qb);

        return $pf;
    }

    protected function getCategoryBySlug($categorySlug)
    {
        $categorySlug = trim($categorySlug, '/');
        $categoryRepository = $this->get('wf_cms.repository.category');
        $category = $categoryRepository->findOneBySlug($categorySlug);

        if (empty($category)) {
            throw $this->createNotFoundException();
        }

        return $category;
    }

    protected function getTagBySlug($tagSlug)
    {
        $tagSlug = trim($tagSlug, '/');
        $tagRepository = $this->get('wf_cms.repository.tag');
        $tag = $tagRepository->findOneBySlug($tagSlug);

        if (empty($tag)) {
            throw $this->createNotFoundException();
        }

        return $tag;
    }

    protected function getCategoryChildren(Category $category)
    {
        $categoryRepository = $this->get('wf_cms.repository.category');

        return $categoryRepository->children($category);
    }

    protected function manageDomainRedirects($slug, $subcategorySlug)
    {
        $categoryDomains = $this->container->getParameter('cms_category_domains');
        $mainDomain = $this->container->getParameter('cms_main_domain');
        $curentHost = $this->getRequest()->getHost();

        foreach ($categoryDomains as $categorySlug => $domainName) {
            if ($slug == $categorySlug && $curentHost != $domainName) {
                $subcategorySlug = trim(str_replace($slug, '' ,$subcategorySlug), '/');
                return $this->redirect('http://'.$domainName . '/' .$subcategorySlug, 301);
            }
            if ($slug != $categorySlug && $curentHost == $domainName) {
                return $this->redirect('http://'.$mainDomain . '/' . $subcategoryslug, 301);
            }
        }
    }

    protected function getPageExcludeIds($categorySlug)
    {
        if ($this->container->has('liip_theme.active_theme')
            && $this->get('liip_theme.active_theme')->getName() == 'phone') {
            return $this->getBoardsExcludeIds(array(
                sprintf('%s/mobile-main', $categorySlug),
                sprintf('%s/mobile-secondary', $categorySlug),
            ));
        }

        return $this->getBoardsExcludeIds(array(
            sprintf('%s/category-main', $categorySlug),
            sprintf('%s/category-secondary', $categorySlug),
        ));
    }

    protected function getBoardsExcludeIds($boardSlugs)
    {
        $pageManager = $this->get('wf_cms.page_manager');
        $boards = array();

        foreach ($boardSlugs as $boardSlug) {
            $board = $pageManager->getPublishedBy('slug', $boardSlug);

            if (!is_null($board)) {
                if (method_exists($board, 'getEmbeddedPages')) {
                    $pageIds = $board->getEmbeddedPages();
                    $boards  = array_merge($boards, $pageIds);
                }
            }
        }

        $boards = array_unique($boards);

        return $boards;
    }

    protected function getInflectedName($categorySlug)
    {
        $categorySlug = trim($categorySlug, "/");
        $categoryAction = lcfirst(Inflector::classify(str_replace('/', '_', $categorySlug)));

        return $categoryAction;
    }


}
