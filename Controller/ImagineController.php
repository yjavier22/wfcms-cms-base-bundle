<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Avalanche\Bundle\ImagineBundle\Controller\ImagineController as BaseImagineController;
use Avalanche\Bundle\ImagineBundle\Imagine\CachePathResolver;
use Avalanche\Bundle\ImagineBundle\Imagine\CacheManager;
use Avalanche\Bundle\ImagineBundle\Imagine\Filter\FilterManager;
use Imagine\Image\ImagineInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;

class ImagineController extends BaseImagineController
{
    /**
     * @var Symfony\Component\HttpFoundation\Request
     */
    private $request;

    /**
     * @var Avalanche\Bundle\ImagineBundle\Imagine\CachePathResolver
     */
    private $cachePathResolver;

    /**
     * @var Imagine\Image\ImagineInterface
     */
    private $imagine;

    /**
     * @var Avalanche\Bundle\ImagineBundle\Imagine\FilterManager
     */
    private $filterManager;

    /**
     * @var Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $webRoot;

    private $sourceRoot;

    public function __construct(
        Request $request, 
        ImagineInterface $imagine, 
        CacheManager $cacheManager, 
        FilterManager $filterManager
    )
    {
        $this->request           = $request;
        $this->cachePathResolver = $cacheManager->cachePathResolver;
        $this->imagine           = $imagine;
        $this->filterManager     = $filterManager;
        $this->filesystem        = $cacheManager->filesystem;
        $this->webRoot           = $cacheManager->webRoot;
        $this->sourceRoot        = $cacheManager->sourceRoot;

        parent::__construct($request, $imagine, $cacheManager, $filterManager);
    }

    public function filter($path, $filter)
    {
        ini_set('max_execution_time', -1);
        ini_set('memory_limit', -1);
        
        if (preg_match('/^(.*?)\.([^\.]*)\.(\d+)-(\d+)-(\d+)-(\d+)\.([a-z]{2,5})$/i', $path, $matches)) {
            //this matches uploads/image/_image_name.module_id.x-y-x2-y2.extension
            list($fullMatch, $prePath, $moduleId, $x, $y, $x2, $y2, $extension) = $matches;
            //TODO!!! if this is not used in the admin, this should not crop
            //or at least it should match the settings in moduleId
            //to prevent "attacks" where custom crops are injected

            $originalName = $prePath . '.' . $extension;
            $croppedName = $path;

            //TODO the crops should be saved in their own cache folder
            //so they can easily be deleted toghether with the other cached files
            //as they can be "easily" recreated from the originals

            /*
             * In order to keep the cropped "originals" in the cache folder (/files/)
             * the $path is smth like /files/crop/uploads/image/xxx
             * while the original image is /uploads/image/xxx
             * Below: doing some mumbo jumbo to get the original name,
             * because imagine doesn't inject imagine.cache_prefix in this controller
             */
            $browserPath = urldecode(urldecode($this->cachePathResolver->getBrowserPath($path, $filter)));
            //browser path is /files/$filter/$path
            if (!preg_match('@^(.*?)/' . $filter . '/@', $browserPath, $matches)) {
                throw new \Exception('could not determine cache prefix');
            }
            $cachePrefix = $matches[1];
            $originalName = substr($originalName, strlen($cachePrefix));
            $originalName = substr($originalName, strlen('/crop'));

            /*
            var_dump('cachePrefix: ' . $cachePrefix);
            var_dump('path: ' . $path); var_dump('filter: ' . $filter);
            var_dump('originalName: ' . $originalName); var_dump('croppedName: ' . $croppedName);
            var_dump('browserPath: ' . $browserPath);
            exit();
            */

            $cropPoint = new Point($x, $y);
            $cropBox = new Box($x2 - $x, $y2 - $y);
            $original = $this->imagine->open($this->webRoot . '/' . $originalName)
                ;

            /*
             * Imagine doesn't create the full dir on save, make sure it exists
             */
            $croppedPath = $this->webRoot . '/' . $croppedName;
            $croppedPaths = explode('/', $croppedPath);
            $croppedFileName = array_pop($croppedPaths);
            $croppedDir = implode('/', $croppedPaths);

            if (!is_dir($croppedDir)) {
                mkdir($croppedDir, 0777, true);
            }

            $original
                ->crop($cropPoint, $cropBox)
                ->save($this->webRoot . '/' . $croppedName)
                ;

            /*
            var_dump($this->webRoot . '/' . $originalName);
            var_dump($original->getSize());
            var_dump($x . ' - ' . $y . ' - ' . $x2 . ' - ' . $y2);
            */
        }

        if (strpos($path, '/content_thumbnails/') !== false) {
            $fullPath = $this->webRoot . '/' . $path;
            if (!file_exists($fullPath)) {
                //avalanche doesn't allow injecting extra params (nor the container) here
                //hardcoding the path should do, for now
                $placeholderPath = $this->webRoot . '/assets/admin/page_placeholder.png';
                $parts = explode('/', $fullPath);
                $fileName = array_pop($parts);
                $directory = implode('/', $parts);

                if (!is_dir($directory)) {
                    if (false === @mkdir($directory, 0777, true)) {
                        throw new FileException(sprintf('Unable to create the "%s" directory', $directory));
                    }
                } elseif (!is_writable($directory)) {
                    throw new FileException(sprintf('Unable to write in the "%s" directory', $directory));
                }

                copy($placeholderPath, $fullPath);
            }
        }
        $response = parent::filter($path, $filter);
        /*
        $response->headers->replace(array(
            'Content-Type' => 'image/jpeg'
        ));
        */

        return $response;
    }
}
