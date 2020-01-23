<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Presta\SitemapBundle\Controller\SitemapController as BaseSitemapController;
use Presta\SitemapBundle\Sitemap\Sitemapindex;
use Presta\SitemapBundle\Sitemap\Urlset;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * handles sitemaps
 *
 * @author cv
 */
class SitemapController extends BaseSitemapController
{
    protected $liveSections = array(
        'articles-current',
        'articles-news'
    );

    protected $categories;

    protected $parameters = array();

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($_format = 'xml')
    {
        $index = new Sitemapindex();
        //some what static sections; as in the url list does not change
        $index->addSitemap($this->getSectionSet('default'));
        $this->addArticleSets($index);
        $index->addSitemap($this->getSectionSet('tags'));

        return $this->getResponse($index->toXml());
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newsAction()
    {
        $this->parameters = array('with_tags' => true);
        return $this->sectionAction('articles-news');
    }

    /**
     * @param string $name
     * @param string $_format
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sectionAction($name, $_format = 'xml')
    {
        ini_set('max_execution_time', -1);
        ini_set('memory_limit', -1);
        $file = $this->getSitemapPath($name);
        $isLive = $this->isSectionLive($name);
        if ($isLive || !file_exists($file)) {
            /* @var $sitemap \Presta\SitemapBundle\Sitemap\DumpingUrlset */
            $sitemap = $this->getGenerator()->get($this->getSectionName($name));
            if (!$sitemap) {
                $sitemap = $this->getGenerator()->newUrlset($name);
            }
            $basedir = dirname($file);
            if (!is_dir($basedir)) {
                mkdir($basedir, 0777, true);
            }
            $sitemap->save($basedir);
        }

        $response = $this->getResponse(file_get_contents($file));
        if ($isLive && is_writable($file)) {
            unlink($file);
        }

        return $response;
    }

    protected function getSectionSet($section = 'default')
    {
        $url = $this->get('router')->generate('PrestaSitemapBundle_section', array('name' => $section, '_format' => 'xml'), true);
        $isLive = $this->isSectionLive($section);
        $file = $this->getSitemapPath($section);
        if (!$isLive && file_exists($file)) {
            $lastMod = new \DateTime('@' . filemtime($file));
        } else {
            $lastMod = new \DateTime();
        }
        return new Urlset($url, $lastMod);
    }

    protected function getSitemapPath($name)
    {
        $url = $this->get('router')->generate('PrestaSitemapBundle_section', array('name' => $name, '_format' => 'xml'));
        $basename = str_replace('sitemap-', '', basename($url));
        $path = $this->container->getParameter('wf_cms_sitemap_path');
        $category = $this->getCategory();
        return $path . DIRECTORY_SEPARATOR . ($category ? $category . DIRECTORY_SEPARATOR : '') . $basename;
    }

    protected function isSectionLive($section)
    {
        return in_array($this->getSectionName($section), $this->liveSections);
    }

    protected function getSectionName($name)
    {
        return preg_replace('/_[0-9]+$/', '', $name);
    }

    protected function getResponse($content = '')
    {
        $response = Response::create($content);
        $response->setPublic();
        $response->setClientTtl($this->getTtl());

        return $response;
    }

    protected function addArticleSets(Sitemapindex $index)
    {
        $categories = $this->getCategories();
        $index->addSitemap($this->getSectionSet('articles-current'));
        $now = date('Y-m');
        $articleRepository = $this->get('wf_cms.repository.page_article');
        $months = $articleRepository->getPublishedMonths($categories);
        sort($months);
        $months = array_reverse($months);
        foreach($months as $month) {
            if ($month != $now) {
                $index->addSitemap($this->getSectionSet('articles-' . $month));
            }
        }
    }

    protected function getCategories()
    {
        if (!$this->categories) {
            $category = $this->getCategory();
            $categoryDomains = array_flip($this->container->getParameter('cms_category_domains'));
            $categoryRepository = $this->container->get('wf_cms.repository.category');
            $this->categories = $categoryRepository->getAllCategories($category, $categoryDomains);
        }

        return $this->categories;
    }

    protected function getCategory()
    {
        $categoryDomains = array_flip($this->container->getParameter('cms_category_domains'));
        return isset($categoryDomains[$_SERVER['HTTP_HOST']]) ? $categoryDomains[$_SERVER['HTTP_HOST']] : null;
    }

    protected function getGenerator()
    {
        $generator = $this->get('wf_cms_sitemap.generator');
        $generator->setBaseUrl($this->getRequest()->getSchemeAndHttpHost() . '/');
        $generator->setParameters(array_merge($this->parameters, array(
            'categories' => $this->getCategories(),
            'category' => $this->getCategory(),
        )));

        return $generator;
    }
}
