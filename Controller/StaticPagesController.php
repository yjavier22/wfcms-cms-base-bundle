<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\Common\Inflector\Inflector;
use Wf\Bundle\CmsBaseBundle\Frontend\Cache\CacheUtil;

class StaticPagesController extends Controller
{

    public function headerJsAction($type='home', $categorySlug='')
    {

        $boardRepository = $this->get('wf_cms.repository.page_board');
        $headerJsBoard = $boardRepository->findOneBySlug('headerjs');

        $rawContent = $this->getRawContent('headerjs');
        $jsObject = $this->getJsObject($type, $categorySlug);

        $ret = new Response($jsObject . "\n". $rawContent);
        $ret->headers->set('Content-Type', 'text/javascript');
        $ret->headers->add(array(
            CacheUtil::EMBEDDED_PAGES_HEADER => $headerJsBoard->getId()
        ));

        return $ret;
    }

    public function footerJsAction()
    {
        $rawContent = $this->getRawContentHTML('footerjs');
        $ret = new Response($rawContent);
        return $ret;
    }


    protected function getRawContent($slug)
    {
        $pageManager = $this->get('wf_cms.page_manager');
        $page = $pageManager->getPublishedBy('slug', $slug);

        if (!$page) {
            return '';    
        }

        $collection = $page->getModulesCollection();
        $module = $collection->getOneById('wfed/free/js');
        if (!$module || !isset($module['data']) || !isset($module['data']['content'])) {
            throw $this->createNotFoundException();
        }
        return trim($module['data']['content']);

    }

    protected function getJsObject($type, $categorySlug) 
    {
        $categoryRepository = $this->get('wf_cms.repository.category');
        $category = $categoryRepository->findOneBySlug($categorySlug);
        if (empty($category)) {
            $slug = 'portada';
        } else {
            $slug = $categorySlug;
        }
        $ret = '
        var SITECONF = {};
        SITECONF.SLUG = "'.$slug.'";
        SITECONF.TYPE = "'.$type.'";
        ';

        return $ret;

    }

    protected function getRawContentHTML($slug)
    {
        $pageManager = $this->get('wf_cms.page_manager');
        $page = $pageManager->getPublishedBy('slug', $slug);
        if (!$page) {
            return '';    
        }

        $collection = $page->getModulesCollection();
        $module = $collection->getOneById('wfed/free/html');
        if (!$module || !isset($module['data']) || !isset($module['data']['content'])) {
            throw $this->createNotFoundException();
        }
        return trim($module['data']['content']);

    }

}
