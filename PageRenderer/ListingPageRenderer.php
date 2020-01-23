<?php

namespace Wf\Bundle\CmsBaseBundle\PageRenderer;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Wf\Bundle\CmsBaseBundle\Templating\HTMLPageAssembler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wf\Bundle\TrackingBundle\Tracker\Tracker;
use Wf\Bundle\CmsBaseBundle\Frontend\Cache\CacheUtil;

class ListingPageRenderer implements PageRendererInterface
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    protected $page = 1;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function setPage($page = 1)
    {
        $this->page = $page;
    }

    public function render(Page $listing, $moduleSettings = null)
    {
        if (!$listing) {
            throw $this->createNotFoundException();
        }

        $settings = $listing->getSettings();
        $listName = $this->getListName($listing);

        if (!$listName) {
            throw $this->createNotFoundException();
        }

        $pagerFactory = $this->get('wf_cms.pager.factory');
        $articleRepository = $this->get('wf_cms.repository.page_article');

        $qb = $articleRepository->getBaseQB();
        $qb->byList($listName);

        /* @var $pages \Pagerfanta\Pagerfanta */
        $pages = $pagerFactory->createPager($qb, 0, null, $settings['perPage']);
        $pages->getAdapter()->setMaxResults(intval($settings['pages']) * intval($settings['perPage']));
        if (!empty($settings['start']) && $pages->getAdapter() instanceof \Wf\Bundle\CmsBaseBundle\Pagerfanta\Adapter\OffsetAdapter) {
            $pages->getAdapter()->setOffset($settings['start']);
        }
        $pages->setCurrentPage($this->page ?: 1);

        $vars = array(
            'listingClass' => $this->getListCssClasses($settings),
            'listing' => $listing,
            'pages' => $pages,
            'moduleSettings' => $moduleSettings,
        );

        $vars = array_merge($vars, $this->getTemplateExtraData($listing));

        $embededPages = array();
        foreach($pages as $page) {
            $embededPages[] = $page->getId();
        }

        $response = new Response($this->get('templating')->render($this->getTemplateName($listing), $vars));
        $response->headers->add(array(
                CacheUtil::EMBEDDED_PAGES_HEADER => implode(',', $embededPages)
            ));
        return $response;
    }

    /**
     * Overwrite this method to add template variables
     */
    protected function getTemplateExtraData(Page $listing)
    {
        return array();
    }

    /**
     * Overwrite this method to change rendered listing template
     */
    protected function getTemplateName(Page $listing)
    {
        return 'WfCmsBaseBundle:Listing:default_listing.html.twig';
    }

    protected function getListCategory($id)
    {
        if (isset($this->category) && $this->category->getId() == $id) {
            return $this->category;
        }

        $categoryRepository = $this->get('wf_cms.repository.category');
        $this->category = $categoryRepository->find($id);

        return $this->category;
    }

    protected function getBlogsCategory()
    {
        if (isset($this->category) && $this->category->getSlug() == 'blogs') {
            return $this->category;
        }

        $categoryRepository = $this->get('wf_cms.repository.category');
        $this->category = $categoryRepository->getBlogsCategory();

        return $this->category;
    }

    protected function getListCssClasses($settings)
    {
        $listingClass = "listing ";

        // content type
        $listingClass .= $settings['content'] . "-listing ";
        $listingClass .= $settings[$settings['content']] . "-listing ";

        if ($settings['category']) {
            $category = $this->getListCategory($settings['category']);
            $listingClass .=  str_replace('/', '-', $category->getSlug()) . '-listing category-listing ';

            if ($category->getParent()) {
                $listingClass .= $category->getParent()->getSlug() . '-listing';
            }
        }

        return $listingClass;
    }

    protected function getListName($listing)
    {
        $settings = $listing->getSettings();
        $listManagerClass = $this->container->getParameter('wf_cms.publish.manager.class');

        //determine what list to use for the query
        if ($settings['content'] == 'contentNews') {
            if ($settings['contentNews'] == 'latest') {//latest list
                if (!empty($settings['category'])) {
                    $listTemplate = !empty($settings['subcategories'])
                                        ? constant($listManagerClass . '::LATEST_CATEGORY')
                                        : constant($listManagerClass . '::LATEST_MAIN_CATEGORY');
                    $category = $this->getListCategory($settings['category']);
                    if (!$category || !$category->isActive()) {
                        throw $this->createNotFoundException();
                    }
                    $listName = sprintf($listTemplate, $category->getId());

                } else {//site wide latest news
                    $listName = constant($listManagerClass . '::LATEST');
                }
            } elseif(in_array($settings['contentNews'], array('mostRead', 'mostCommented', 'mostShared'))) {//tracker list
                switch ($settings['contentNews']) {
                    case 'mostCommented':
                        $namespace = Tracker::NAMESPACE_COMMENTS;
                        break;
                    case 'mostShared':
                        $namespace = Tracker::NAMESPACE_SHARES;
                        break;
                    default:
                        $namespace = Tracker::NAMESPACE_PAGEVIEWS;
                        break;
                }

                if (!empty($settings['category'])) {
                    $category = $this->getListCategory($settings['category']);
                    if (!$category || !$category->isActive()) {
                        throw $this->createNotFoundException();
                    }
                    $listTemplate = implode(':', array($namespace, Tracker::CATEGORY_LIST_NAME));
                    $listName = sprintf($listTemplate, $category->getId());
                } else {
                    $listName = implode(':', array($namespace, Tracker::MOST_LIST_NAME));
                }
            }
        } elseif ($settings['content'] == 'contentTags') {//tag list
            $tagValue = $settings['contentTags'];
            if (is_numeric($tagValue)) {
                $tagId = $tagValue;
            } else {
                $tag = $this->get('wf_cms.repository.tag')->findOneByTitle($settings['contentTags']);
                if (!$tag) {
                    throw $this->createNotFoundException();
                }
                $tagId = $tag->getId();
            }
            $listTemplate = constant($listManagerClass . '::LATEST_TAGS');
            $listName = sprintf($listTemplate, $tagId);
        } elseif ($settings['content'] == 'contentAuthor') {
            $author = $this->get('wf_cms.repository.user')->find($settings['contentAuthor']);
            $listTemplate = constant($listManagerClass . '::LATEST_AUTHOR');

            $listName = sprintf($listTemplate, $author->getId());
        } elseif ($settings['content'] == 'contentBlogs') {
            // get category
            if (!empty($settings['blogCategory'])) {
                $category = $this->getListCategory($settings['blogCategory']);
            } else {
                $category = $this->getBlogsCategory();
            }

            if (!$category || !$category->isActive()) {
                throw $this->createNotFoundException();
            }

            if ($settings['contentBlogs'] == 'latest') {
                $listTemplate = constant($listManagerClass . '::LATEST_CATEGORY');
            } elseif ($settings['contentBlogs'] == 'mostRead') {
                $namespace = Tracker::NAMESPACE_PAGEVIEWS;
                $listTemplate = implode(':', array($namespace, Tracker::CATEGORY_LIST_NAME));
            }

            $listName = sprintf($listTemplate, $category->getId());
        }

        return $listName;
    }

    protected function get($serviceId)
    {
        return $this->container->get($serviceId);
    }

    protected function createNotFoundException()
    {
        return new NotFoundHttpException();
    }

}
