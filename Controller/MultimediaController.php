<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\ArrayAdapter;


class MultimediaController extends Controller
{
    protected $maxItems = 45;
    protected $itemsPerPage = 9;
    protected $maxTrackedItems = 6;

    /**
     * @Template()
     */
    public function videoIndexAction()
    {
        return array();
    }

    /**
     * @Template()
     */
    public function videoAction($id, $slug)
    {
        $video = $this->get('wf_cms.repository.video')->find($id);
        $page = $this->get('wf_cms.repository.page_article')->findOneBySlug($slug);

        $videoPlayer = null;

        if ($video->getSource() == 'embedded') {
            $videoPlayer = $video->getMediaId();
        } elseif ($video->getMediaId()) {
            $modules = $page->getModulesCollection()->getexternalVideoModules();

            foreach ($modules as $modulesData) {
                if ($modulesData['id'] == $id) {
                    $videoPlayer = $modulesData['html'];
                }
            }
        }

        return array(
            'videoPlayer' => $videoPlayer,
            'video' => $video,
            'page' => $page);
    }

    /**
     * @Template()
     */
    public function galleryIndexAction()
    {
        return array();
    }

    /**
     * @Template()
     */
    public function galleryAction($slug)
    {
        $page = $this->get('wf_cms.repository.page_article')->findOneBySlug($slug);
        $images = $this->get('wf_cms.repository.image')->getMediaByIds($page->getImages());

        return array(
            'page' => $page,
            'images' => $images,
        );
    }

    /**
     * @Template()
     */
    public function audioIndexAction()
    {
        return array();
    }

    /**
     * @Template()
     * @param integer $id
     * @param string $slug
     */
    public function audioAction($id, $slug)
    {
        $audio = $this->get('wf_cms.repository.audio')->find($id);
        $page = $this->get('wf_cms.repository.page_article')->findOneBySlug($slug);

        return array(
            'audio' => $audio,
            'page' => $page,
        );
    }

    /**
     * @Template()
    */
    public function mostViewedAction($type = 'video')
    {
        $media = array();

        // todo: parameter with most viewed limit?
        $ids = $this->get('wf_tracking.repository')->getMost('trk:' . $type, 0, $this->maxTrackedItems);
        if ($ids) {

            if ($type == 'gallery') {
                $media = $this->get('wf_cms.repository.page_article')->find($ids);
            }

            $media = $this->get('wf_cms.repository.' . $type)->getMediaByIds($ids);
        }

        return array(
            'type' => $type,
            'media' => $media
        );
    }

    /**
     * @Template()
     */
    public function moreGalleryAction($page)
    {
        $excludeIds = $this->getExcludesFromRequest();

        $items = $this->get('wf_cms.repository.page_article')->getMediaPages('image', $this->maxItems, $excludeIds);

        $adapter = new ArrayAdapter($items);
        $pages = new Pagerfanta($adapter);
        $pages->setMaxPerPage($this->itemsPerPage)
            ->setCurrentPage($page);

        return array(
            'pages' => $pages
        );
    }


    /**
     * @Template()
     */
    public function moreVideoAction($page)
    {
        $excludeIds = $this->getExcludesFromRequest();

        // otherwise exclude the video from main board
        $board = $this->get('wf_cms.page_manager')->getPublishedBy('slug', 'multimedia-main');
        if (!$excludeIds) {
            $excludeIds = $board->getEmbedded('video');
        }

        $media = array();
        $videoIds = array();
        $articles = array();
        $items = array();

        $pageArticles = $this->get('wf_cms.repository.page_article')->getMediaPages('video', $this->maxItems);
        foreach ($pageArticles as $art) {
            $pageMedia = $art->getVideos();
            if (!is_array($pageMedia)) {
                $pageMedia = array($pageMedia);
            }

            $pagePublishedDate = $art->getPublishedAt();
            foreach ($pageMedia as $id) {
                $articles[$id]['date'] = $pagePublishedDate;
                $articles[$id]['id'] = $art->getId();
                $articles[$id]['slug'] = $art->getSlug();
                $articles[$id]['title'] = $art->getTitle();

                // exclude currently played video
                if (in_array($id, $excludeIds)) {
                    continue;
                }

                $videoIds[] = $id;
            }
        }

        if ($videoIds) {
            $items = $this->get('wf_cms.repository.video')->getMediaByIds($videoIds);
        }

        $adapter = new ArrayAdapter($items);
        $pages = new Pagerfanta($adapter);
        $pages->setMaxPerPage($this->itemsPerPage)
            ->setCurrentPage($page);

        $items = $pages->getCurrentPageResults();

        return array(
            'items' => $items,
            'pages' => $pages,
            'articles' => $articles
        );
    }

    /**
     * @Template()
     * @param integer $page
     */
    public function moreAudioAction($page)
    {
        $excludeIds = $this->getExcludesFromRequest();

        // otherwise exclude the video from main board
        $board = $this->get('wf_cms.page_manager')->getPublishedBy('slug', 'multimedia-main');
        if (!$excludeIds) {
            $excludeIds = $board->getEmbedded('audio');
        }

        $media = array();
        $audioIds = array();
        $articles = array();
        $items = array();

        $pageArticles = $this->get('wf_cms.repository.page_article')->getMediaPages('audio', $this->maxItems);
        foreach ($pageArticles as $art) {
            $pageMedia = $art->getAudios();
            if (!is_array($pageMedia)) {
                $pageMedia = array($pageMedia);
            }

            $pagePublishedDate = $art->getPublishedAt();
            foreach ($pageMedia as $id) {
                $articles[$id]['date'] = $pagePublishedDate;
                $articles[$id]['id'] = $art->getId();
                $articles[$id]['slug'] = $art->getSlug();
                $articles[$id]['title'] = $art->getTitle();

                // exclude currently played video
                if (in_array($id, $excludeIds)) {
                    continue;
                }

                $audioIds[] = $id;
            }
        }

        if ($audioIds) {
            $items = $this->get('wf_cms.repository.audio')->getMediaByIds($audioIds);
        }

        $adapter = new ArrayAdapter($items);
        $pages = new Pagerfanta($adapter);
        $pages->setMaxPerPage($this->itemsPerPage)
            ->setCurrentPage($page);

        $items = $pages->getCurrentPageResults();

        return array(
            'items' => $items,
            'pages' => $pages,
            'articles' => $articles
        );
    }

    protected function getExcludesFromRequest()
    {
        $excludeIds = array();
        if ($exclude = $this->get('request')->query->get('exclude', null) ) {
            if (strpos($exclude, ',')) {
                $excludeIds = explode(',', $exclude);
            } else {
                $excludeIds[] = $exclude;
            }
        }

        return $excludeIds;
    }
}