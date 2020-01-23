<?php

namespace Wf\Bundle\CmsBaseBundle\Manager;

/**
 * add category slug to global REQUEST_URI if is dedicated domain
 * replace in response category slug if is dedicated domain
 *
 */
class DomainManager
{
    public static function replacedGlobals($cmsDomains)
    {
        $isDedicated = false;
        foreach ($cmsDomains as $slug => $domain) {
            if ( $_SERVER['HTTP_HOST'] == $domain) {
                $requestUri = $_SERVER['REQUEST_URI'];
                $queryString = $_SERVER['QUERY_STRING'];
                $requestUri = str_replace('?' . $_SERVER['QUERY_STRING'], '', $requestUri);

                if ($requestUri == '/') {
                    $requestUri = '/' . $slug . $requestUri;
                    if (!empty($queryString)) {
                        $requestUri.= '?' . $queryString;
                    }
                    $_SERVER['REQUEST_URI'] = $requestUri;
                }

                $isDedicated = $slug;
            }
        }

        return $isDedicated;
    }

    public static function addPrefix($cmsDomains, $slug) {
        foreach ($cmsDomains as $categorySlug => $domain) {
            if ( isset($_SERVER['HTTP_HOST']) 
                && $_SERVER['HTTP_HOST'] == $domain 
                && $categorySlug != trim($slug, '/')) {
                $slug = trim($slug,'/');
                $slug = $categorySlug . '/' . $slug;
            }
        }
        return $slug;
    }
}
