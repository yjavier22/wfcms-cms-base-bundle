<?php

namespace Wf\Bundle\CmsBaseBundle\Frontend\Cache;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Liip\CacheControlBundle\Helper\Varnish;
use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CacheManager
{
    /**
     * @var Varnish
     */
    protected $cacheControl;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * The array of domains for which Varnish caches pages
     * @var array
     */
    protected $domains;

    public function __construct(RouterInterface $router, LoggerInterface $logger, ContainerInterface $container, $domains)
    {
        $this->router = $router;
        $this->logger = $logger;
        $this->domains = $domains;

        if ($container->has('liip_cache_control.varnish')) {
            $this->cacheControl = $container->get('liip_cache_control.varnish');
        }
    }

    public function purgePageCache(Page $page)
    {
        if (!$this->isEnabled()) {
            $this->logger->info('No cache to purge liip_cache_control.varnish is not defined, most likely because there are no varnish servers defined');
            return false;
        }

        if (empty($page)) {
            return false;
        }

        $response = array(
            'embedded-pages' => $this->purgeEmbeddedPages($page),
        );

        $routes = $this->getPageRoutes($page);
        $response['routes'] = $this->purgeRoutes($routes);

        $this->logger->info(sprintf('Purged page cache response %s', json_encode($response)));
    }


    public function purgeEmbeddedPages($page)
    {
        $purgeThis = sprintf('obj.http.%s ~ ^%2$d\D|\D%2$d\D|\D%2$d$',
            CacheUtil::EMBEDDED_PAGES_HEADER,
            $page->getId());
        $response = $this->cacheControl->invalidatePath('/page', array(
            CURLOPT_HTTPHEADER => array(sprintf('purgethis: %s', $purgeThis))
        ));
        // error_log(sprintf('Purged embedded pages for (%s) response %s', $page->getId(), json_encode($response)));

        return $response;
    }


    protected function getPageRoutes($page)
    {
        $routes = array();
        $routes[] = $this->generateUrl('wf_page_show', array('slug' => $page->getSlug()));
        $routes[] = $this->generateUrl('wf_article_show', array('articleSlug' => $page->getSlug()));
        if (class_exists('Wf\Bundle\CmsRssBundle\WfCmsRssBundle')) {
            $routes[] = $this->generateUrl('wf_cms_rss_article', array('articleSlug' => $page->getSlug()));
            $routes[] = $this->generateUrl('wf_cms_rss_latest', array());
            $category = $page->getCategory();
            if ($category !== null) {
                $categorySlug = $category->getSlug();
                $routes[] = $this->generateUrl('wf_cms_rss_category', array('categorySlug' => $categorySlug));
            }
        }
        //XXX: categories are purged from separate job

        return $routes;
    }

    public function purgeCategoriesCache($categories)
    {
        if (!$this->isEnabled()) {
            $this->logger->info('No cache to purge liip_cache_control.varnish is not defined, most likely because there are no varnish servers defined');
            return false;
        }

        if (empty($categories)) {
            return;
        }

        $purgeThis = "obj.http.X-Cache-Category-Id ~ ^(" . implode('|', $categories) . ")$";
        $response = $this->cacheControl->invalidatePath('/page', array(
            CURLOPT_HTTPHEADER => array(sprintf('purgethis: %s', $purgeThis))
        ));
//        error_log(sprintf('Purged categories (%s) response %s', implode('|', $categories), json_encode($response)));

        $this->logger->info(sprintf('Purged categories cache response %s', json_encode($response)));
    }

    public function purgeRoutes($routes)
    {
        $purgeThis = "obj.http.x-url ~ " . implode('|', $routes);
        $response = $this->cacheControl->invalidatePath('/page', array(
            CURLOPT_HTTPHEADER => array(sprintf('purgethis: %s', $purgeThis))
        ));
//        error_log(sprintf('Purged routes (%s) response %s', implode('|', $routes), json_encode($response)));

        return $response;
    }

    public function purgeRatingsCache($id)
    {
        if (!$this->isEnabled()) {
            $this->logger->info('No cache to purge liip_cache_control.varnish is not defined, most likely because there are no varnish servers defined');
            return false;
        }

        $url = $this->generateUrl('wf_cms_rating', array('id' => $id));
        $response = $this->cacheControl->invalidatePath($url);

       //error_log(sprintf('Purged ratings cache response %s', json_encode($response)));
        $this->logger->info(sprintf('Purged ratings cache response %s', json_encode($response)));
    }

    public function purgeAdvertisementCache()
    {
        if (!$this->isEnabled()) {
            $this->logger->info('No cache to purge liip_cache_control.varnish is not defined, most likely because there are no varnish servers defined');
            return false;
        }

        $url = $this->generateUrl('wf_cms_advertisement_pages', array());
        $response = $this->invalidatePath($url);

        // error_log(sprintf('Purged advertisement cache response %s', json_encode($response)));
        $this->logger->info(sprintf('Purged advertisement cache response %s', json_encode($response)));
    }

    public function purgeCommentsCache($id)
    {
        if (!$this->isEnabled()) {
            $this->logger->info('No cache to purge liip_cache_control.varnish is not defined, most likely because there are no varnish servers defined');
            return false;
        }

        if (empty($id)) {
            $this->logger->info('Error: no comment thread id');
            return false;
        }

        $url = $this->generateUrl('ep_comment_get_thread_comments', array('id' => $id, 'page' => '1')) . '/1'; //page=1 is the default so the router doesn't append it to the URL
        $response = $this->invalidatePath($url);
        $this->logger->info(sprintf('Purged comment cache response %s', json_encode($response)));
    }

    public function purgeCommentVotesCache($commentId)
    {
        if (!$this->isEnabled()) {
            $this->logger->info('No cache to purge liip_cache_control.varnish is not defined, most likely because there are no varnish servers defined');
            return false;
        }

        if (empty($commentId)) {
            $this->logger->info('Error: ids missing');
            return false;
        }

        $url = $this->generateUrl('ep_comment_votes', array('id' => $commentId));

        $response = $this->cacheControl->invalidatePath($url);

        $this->logger->info(sprintf('Purged comment vote cache response %s', json_encode($response)));
    }

    protected function generateUrl($routeName, $routeParams)
    {
        $url = $this->router->generate($routeName, $routeParams);
        $url = preg_replace('@^/view@i', '', $url);

        return $url;
    }

    protected function isEnabled()
    {
        return !($this->cacheControl == null);
    }

    protected function invalidatePath($path)
    {
        $ret = array();
        $ret['main'] = $this->cacheControl->invalidatePath($path);

        if (!is_array($this->domains)) {
            return $ret;
        }

        foreach ($this->domains as $domain) {
            $ret[$domain] = $this->cacheControl->invalidatePath($path, array(
                CURLOPT_HTTPHEADER => array(sprintf('Host: %s', $domain))
            ));
        }

        return $ret;
    }
}