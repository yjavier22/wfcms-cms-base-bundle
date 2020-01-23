<?php 
namespace Wf\Bundle\CmsBaseBundle\Content\Thumbnail;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Knp\Snappy\Image;
use Symfony\Component\Routing\Router;
use Avalanche\Bundle\ImagineBundle\Imagine\CachePathResolver;
use Avalanche\Bundle\ImagineBundle\Controller\ImagineController;

class Generator 
    implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    
    /**
    /**
     * @var Image
     */
    protected $knpSnappyImage;
    
    /**
     * @var CachePathResolver
     */
    protected $imagineCachePathResolver;
    
    /**
     * @var ImagineController
     */
    protected $imagineController;
    
    /**
     * @var Router
     */
    protected $router;
    
    protected $webRoot;
    protected $uploadsRoot;
    
    /**
     * uploads dir path relative to the webdir
     * @var string
     */
    protected $uploadsRelativePath;
    
    /**
     * the page show route
     * @var array
     */
    protected $pageRoute;
    
    /**
     * If app is in debug mode, don't redirect to the final image, to ease debugging
     * @var boolean
     */
    protected $debug;
    
    public function __construct($knpSnappyImage, $imagineCachePathResolver, $router, $webRoot, $uploadsRoot, $pageRoute, $debug, $logger)
    {
        $this->knpSnappyImage = $knpSnappyImage;
        $this->imagineCachePathResolver = $imagineCachePathResolver;
        $this->router = $router;
        $this->webRoot = $webRoot;
        $this->uploadsRoot = $uploadsRoot;
        $this->pageRoute = $pageRoute;
        $this->debug = $debug;
        $this->logger = $logger;

        $this->logger->debug(sprintf('[ThumbnailGenerator]Starting'));
        
        $parts = explode($this->webRoot, $this->uploadsRoot);
        $this->uploadsRelativePath = array_pop($parts);

        if (getenv('SF_HOST')) {
            $this->router->getContext()->setHost(getenv('SF_HOST'));
        }
    }
    
    public function page($article, $filter = null, $force = false)
    {
        $url = $this->router->generate($this->pageRoute, array('slug' => $article->getSlug()), true);
        return $this->thumbnail($article, $filter, $url, $force);
    }
    
    protected function generateThumbnailFromHTML($html, $fullPath)
    {
        $this->knpSnappyImage->getOutputFromHtml($html, $fullPath);
    }
    
    protected function generateThumbnailFromUrl($url, $fullPath)
    {
        $this->logger->debug(sprintf('[ThumbnailGenerator]Generating from url (%s) to file (%s)', $url, $fullPath));
        try {
            $this->knpSnappyImage->generate($url, $fullPath);
        } catch (\RuntimeException $e) {
            $this->logger->warn(sprintf('[ThumbnailGenerator]Error generating: %s', $e->getMessage()));
        }
    }

    protected function getAllFilters() {
        $filterConfig = $this->container->getParameter('imagine.filters');
        return array_keys($filterConfig);
    }

    protected function clearThumbCache($relativePath, $filter) {
        $thumbnailUrl = $this->imagineCachePathResolver->getBrowserPath($relativePath, $filter);

        //remove the app_ENV.php controller, if any
        $thumbnailUrl = preg_replace('@/[^/]*.php/@i', '/', $thumbnailUrl);

        $thumbnailPath = $this->webRoot . $thumbnailUrl;

        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
    }

    public function clearThumbsCache($entity) {
        $className = join('', array_slice(explode('\\', get_class($entity)), -1));
        $entityDirName = strtolower($className);

        $relativePath = $this->uploadsRelativePath . '/content_thumbnails/' . $entityDirName . '/' . $entity->getSlug() . '.jpg';
        $filters = $this->getAllFilters();
        foreach($filters as $filter) {
            $this->clearThumbCache($relativePath, $filter);
        }

    }
    
    public function thumbnailRelativePath($entity, $filter = null) {
        $className = join('', array_slice(explode('\\', get_class($entity)), -1));
        $entityDirName = strtolower($className);
        $logger = $this->container->get('logger');
        return $relativePath = (isset($filter) ? '/files/' . $filter . '/' : '') . $this->uploadsRelativePath . '/content_thumbnails/' . $entityDirName . '/' . $entity->getSlug() . '.jpg';
    }
    
    public function thumbnail($entity, $filter, $url, $force = false)
    {
        $relativePath = $this->thumbnailRelativePath($entity);
        //var_dump(filemtime($fullPath)); var_dump($entity->getUpdatedAt()->getTimestamp()); exit();
        $fullPath = $this->webRoot . '/' . $relativePath;
        if (!file_exists($fullPath) 
            || filemtime($fullPath) < $entity->getUpdatedAt()->getTimestamp()
            || $force) {
            if (file_exists($fullPath)) {
                unlink($fullPath);
                //echo 'unlinked';
            }
            //echo $fullPath;
            //exit();
            $this->generateThumbnailFromUrl($url, $fullPath);
            $this->clearThumbsCache($entity);
            if (!file_exists($fullPath)) {
                return false;
            }
        }
        
        if (empty($filter)) {
            return true;
        }

        $path = $this->imagineCachePathResolver->getBrowserPath($relativePath, $filter);
        $fullPath = $this->webRoot . '/' . $path;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        
        return;
        //$ret = $this->imagineController->filter($relativePath, $filter);

        if ($this->debug && $ret->getStatusCode() != 200 && $ret->getStatusCode() != 201) {
            $ret->setStatusCode(200);
            $ret->headers->replace(array());
        }
        
        return $ret;
    }
    
    function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
        try {
            $this->imagineController = $this->container->get('imagine.controller');//scoped as request; no way to use it cli context
        } catch (\Symfony\Component\DependencyInjection\Exception\InactiveScopeException $e) {
            
        }
    }
}
