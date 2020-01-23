<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Zend\Feed\Writer\Writer;

use Zend\Feed\Writer\Feed;

class RssController extends Controller
{
    protected $pageImageHelper;
    protected $limit = 20;

    /**
     *
     */
    public function mostAction(Request $request)
    {
        $translator = $this->get('translator');

        $pages = $this->get('wf_cms.page_manager')
            ->getTrackingPages('pageviews', 0, 9);

        $feed = $this->generateFeed($pages);

        $feed->setTitle($translator->trans('rss.title.most_pageviews', array(), 'WfCmsRSS'));
        $feed->setDescription($translator->trans('rss.description.most_pageviews', array(), 'WfCmsRSS'));

        return $this->feedResponse($feed, $this->getFeedFormat($request));
    }

    /**
     *
     */
    public function multimediaAction($mediaType, Request $request)
    {
        $translator = $this->get('translator');

        if (!in_array($mediaType, array('video', 'image', 'audio'))) {
            throw new \Exception("Multimedia of type: ".$mediaType." does not exist.", 1);
        }

        $pages = $this->get('wf_cms.repository.page_article')
            ->getMediaPages($mediaType);

        $feed = $this->generateFeed($pages);
        $feed->setTitle($translator->trans('rss.title.multimedia', array(), 'WfCmsRSS'));
        $feed->setDescription($translator->trans('rss.description.multimedia', array(), 'WfCmsRSS'));

        return $this->feedResponse($feed, $this->getFeedFormat($request));
    }

    /**
     *
     */
    public function latestAction(Request $request)
    {
        $translator = $this->get('translator');
        $pageRepository = $this->get('wf_cms.repository.page_article');

        $pages = $pageRepository->getLatestPublished(null, null, $this->limit);

        $feed = $this->generateFeed($pages);
        $feed->setTitle($translator->trans('rss.title.latest', array(), 'WfCmsRSS'));
        $feed->setDescription($translator->trans('rss.description.latest', array(), 'WfCmsRSS'));

        return $this->feedResponse($feed, $this->getFeedFormat($request));
    }

    /**
     *
     */
    public function categoryAction($categorySlug, Request $request)
    {
        $translator = $this->get('translator');
        $categoryRepository = $this->get('wf_cms.repository.category');
        $pageRepository = $this->get('wf_cms.repository.page_article');

        $category = $this->get('wf_cms.repository.category')->findOneBySlug($categorySlug);

        if (!$category) {
            throw $this->createNotFoundException();
        }

        $categoryChildren = $categoryRepository->children($category);
        $pages = $pageRepository->getLatestPublished(null, $category, $this->limit, false, $categoryChildren);

        $feed = $this->generateFeed($pages, $category);
        $feed->setTitle($translator->trans('rss.title.category', array('%category%' => $category->getTitle()), 'WfCmsRSS'));
        $feed->setDescription($translator->trans('rss.description.category', array('%category%' => $category->getTitle()), 'WfCmsRSS'));

        return $this->feedResponse($feed, $this->getFeedFormat($request));
    }


    public function generateFeed($pages, $category = null)
    {
        $this->pageImageHelper = $this->get('wf_cms.twig.image_extension');
        $request = $this->get('request');
        $translator = $this->get('translator');
        $router = $this->get('router');

        $baseUrl = $router->generate('wf_homepage', array(), $absolute = true);

        if (!Writer::isRegistered('WfFeedExtension')) {
            $extensions = Writer::getExtensionManager();

            $extensions->setInvokableClass('WfFeedEntry',
                'Wf\Bundle\CmsBaseBundle\Feed\WfCmsFeedExtension\Entry');
            $extensions->setInvokableClass('WfFeedRendererEntry',
                'Wf\Bundle\CmsBaseBundle\Feed\WfCmsFeedExtension\Renderer\Entry');

            Writer::registerExtension('WfFeed');
        }

        $feed = new Feed();
        $feed->setType( $this->getFeedFormat($this->get('request')) );

        $feed->setLink($baseUrl);
        $feed->setFeedLink($baseUrl . $request->getPathInfo(), 'atom');
        $feed->setDateModified(time());
        $feed->setGenerator('Wf Feed Generator ('.$baseUrl.')');

        $ids = array();
        $categories = array();
        foreach ($pages as $page) {
            if (!is_object($page)) {
                $page = $page[0];
            }
            $ids[] = 'article-'.$page->getId();

            $cat = $page->getCategory();
            $categories[$cat->getId()] = array(
                'id' => $cat->getId(),
                'term' => $cat->getDescription(),
                'sector' => $this->getSector($cat),
            );
        }

        foreach ($pages as $page) {
            if (!is_object($page)) {
                $page = $page[0];
            }

            $category = $page->getCategory();

            $entry = $feed->createEntry();
            $entry->setTitle($page->getTitle());
            $entry->setSubtitle($page->getEpigraph());
            $entry->setCategory($category->getTitle());

            $pageUrl = $router->generate('wf_article_show', array(
                'articleSlug' => $page->getSlug(),
            ), $absolute = true);
            $entry->setLink($pageUrl);

            $description = $page->getShortDescription();
            if (!empty($description)) {
                $entry->setDescription($description);
            }

            if ($author = $page->getAuthor()) {
                $entry->setAuthor($author->getName());
            }

            $entry->setDateCreated($page->getPublishedAt()->getTimestamp());

            $mainImage = $page->getMainImage();
            if ($mainImage) {
                $image = $mainImage->getImage();
                if(is_file($image)) {
                    $imageUrl = $this->pageImageHelper->wfCmsRenderPageImage($page);
                    $entry->setEnclosure(array(
                        'uri' => $baseUrl . $imageUrl,
                        'length' => $image->getSize(),
                        'type' => $image->getMimeType(),
                    ));
                }
            }

            $feed->addEntry($entry);
        }

        return $feed;
    }


    protected function getSector($category)
    {
        return 1;
    }


    protected function getFeedFormat(Request $request)
    {
        return 'rss';
    }


    protected function feedResponse($feed, $type = 'rss')
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent($feed->export($type));

        return $response;
    }
}
