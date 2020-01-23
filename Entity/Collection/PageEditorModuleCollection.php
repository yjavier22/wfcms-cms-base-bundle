<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Collection;

use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Wf\Bundle\CommonBundle\Doctrine\ArrayDB\ArrayDB;
use Wf\Bundle\CmsBaseAdminBundle\Configuration\EditorModulesConfiguration as ModuleConfig;
use Wf\Bundle\CmsBaseBundle\Entity\Image;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Collections\Collection;
use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Wf\Bundle\CmsBaseBundle\Entity\Category;
use Doctrine\Common\Util\Inflector;
use Symfony\Component\Routing\Exception\ExceptionInterface as RouterException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author gk
 */
class PageEditorModuleCollection extends ArrayDB
{
    protected $template;

    /**
     * Mappings for the uploader used in the app. Useful for determining
     * assets' relative path
     * @var array
     */
    protected $uploaderMappings;

    protected $initialized = false;

    /**
     * @var array
     */
    protected $specialModuleIds = array(
        'title' => 'wfed/title/page_title',
        'main_image' => 'wfed/image/simple',
        'main_video' => 'wfed/video/simple',
        'image' => 'wfed/image/simple',
        'video' => 'wfed/video/simple',
        'audio' => 'wfed/audio/simple',
        'supra' => 'wfed/title/supra',
        'paragraph' => 'wfed/body_text/paragraph',
        'subtitle' => 'wfed/body_text/subtitle',
        'related' => 'wfed/collection/related',
        'related_items' => array('wfed/composite/related_page'),
        'related_page_inner' => 'wfed/body_text/inline',
        'sambatech' => 'wfed/video/sambatech',
        'youtube' => 'wfed/video/youtube',
    );

    protected $titleSelector = '.article .title';
    protected $titleHTMLFormat = '<h1>%s</h1>';

    protected $imagesSelector = '.article .images';
    protected $imageHTMLFormat = '<div><img src="%s" /></div>';
    protected $videosSelector = '.article .videos';

    protected $mainImageSelector = '.article .image';
    protected $mainVideoSelector = '.article .video';

    protected $epigraphSelector = '.article .intro';
    protected $epigraphHTMLFormat = '<p>%s</p>';

    protected $textContentSelector = '.article .body';
    protected $paragraphHTMLFormat = '<p>%s</p>';
    protected $subtitleHTMLFormat = '<h3>%s</h3>';

    protected $relatedSelector = '.article .sidebar .subcolumnas';
    protected $pageLinkHTMLFormat = '<a href="%s">%s</a>';
    protected $pageLinkItemHTMLFormat = '<li class="page-anchor">%s</li>';
    protected $relatedHTMLFormat = '<ul class="collection page-collection">%s</ul>';

    /**
     * @var Category
     */
    protected $category;

    /**
     * @var array
     */
    protected $specialModulePackage = array(
        'image' => 'wfed/image',
        'video' => 'wfed/video',
        'audio' => 'wfed/audio',
        'paragraph' => 'wfed/body_text'
    );

    /**
     * The service container - used to get module renderer services
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var FragmentHandler
     */
    protected $fragmentHandler;

    private $compositeCollections = array();

    /**
     * @var ModuleConfig
     */
    protected $moduleConfig;

    public function __construct(array $elements = array())
    {
        parent::__construct($elements);

        foreach ($this as &$element) {
            $this->setDefaults($element);
        }
    }

    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function setModuleConfig($moduleConfig)
    {
        $this->moduleConfig = $moduleConfig;
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        $this->fragmentHandler  = $this->container->get('fragment.handler');
        $this->router = $this->container->get('router');
        $this->kernel = $this->container->get('kernel');
    }

    public function getOneById($id, $recursive = true)
    {
        foreach ($this as $moduleData) {
            if ($moduleData['moduleId'] == $id) {
                return $moduleData;
            }
        }

        if ($recursive) {
            foreach ($this as $moduleData) {
                if ($subcollection = $this->getCompositeCollection($moduleData)) {
                    if ($module = $subcollection->getOneById($id)) {
                        return $module;
                    }
                }
            }
        }
    }

    public function getAllById($id, $recursive = true)
    {
        $ret = array();

        foreach ($this as $moduleData) {
            if ($recursive && $subcollection = $this->getCompositeCollection($moduleData)) {
                $ret = array_merge($ret, $subcollection->getAllById($id));
            }

            if ($moduleData['moduleId'] == $id) {
                $ret[] = $moduleData;
            }
        }

        return $ret;
    }

    public function getAllByIds($ids, $recursive = true)
    {
        $ret = array();

        foreach ($this as $moduleData) {
            if ($recursive && $subcollection = $this->getCompositeCollection($moduleData)) {
                $ret = array_merge($ret, $subcollection->getAllByIds($ids));
            }

            if (in_array($moduleData['moduleId'], $ids)) {
                $ret[] = $moduleData;
            }
        }

        return $ret;
    }

    protected function isModulePackage($moduleData, $package) {
        return strpos($moduleData['moduleId'], $package) === 0;
    }

    public function getOneByPackage($package, $recursive = true)
    {
        foreach ($this as $moduleData) {
            if ($this->isModulePackage($moduleData, $package)) {
                return $moduleData;
            }

            if ($recursive && $subcollection = $this->getCompositeCollection($moduleData)) {
                if ($module = $subcollection->getOneByPackage($package)) {
                    return $module;
                }
            }
        }
    }

    public function getAllByPackage($package, $recursive = true)
    {
        $ret = array();
        foreach ($this as $moduleData) {
            if ($recursive && $subcollection = $this->getCompositeCollection($moduleData)) {
                $ret = array_merge($ret, $subcollection->getAllByPackage($package));
            }

            if ($this->isModulePackage($moduleData, $package)) {
                $ret[] = $moduleData;
            }
        }

        return $ret;
    }

    public function getAllSelectors()
    {
        $selectors = array();
        foreach ($this as $moduleData) {
            if (!empty($moduleData['selector']) && !isset($selectors[$moduleData['selector']])) {
                $selectors[$moduleData['selector']] = true;
            }
            if (0 && empty($moduleData['selector'])) {
                error_log('[EMPTY SELECTOR]' . json_encode($moduleData));
            }
        }

        return array_keys($selectors);
    }

    /**
     * @param array $moduleList
     * @return \self
     */
    public function getCompositeCollectionObject($moduleList = array())
    {
        $collection = new self($moduleList);
        $collection->setModuleConfig($this->moduleConfig);
        $collection->setContainer($this->container);

        return $collection;
    }

    public function getCompositeCollection($moduleData)
    {
        if (!isset($moduleData['data']['moduleList'])
            || empty($moduleData['data']['moduleList'])
            || !is_array($moduleData['data']['moduleList'])) {

            if (!empty($moduleData['data']['collection']) && is_array($moduleData['data']['collection'])) {
                $moduleData['data']['moduleList'] = array();
                foreach($moduleData['data']['collection'] as $item) {
                    if (isset($item['id']) & !isset($item['moduleId'])) {
                        $item['moduleId'] = $item['id'];
                    }
                    unset($item['id']);
                    $moduleData['data']['moduleList'][] = $item;
                }
            } else {
                return null;
            }
        }

        $id = $moduleData['id'];
        if (!isset($this->compositeCollections[$id])) {
            $moduleList = $moduleData['data']['moduleList'];
            $collectionData = !empty($moduleData['data']['collection']) ? $moduleData['data']['collection'] : array();
            foreach ($moduleList as $index => &$subModuleData) {
                if (isset($collectionData[$index]) && is_array($collectionData[$index])) {
                    $subModuleData = array_merge($subModuleData, $collectionData[$index]);
                }
                //composite modules keep the id of their submodules in the 'id' key
                //the parent collection expects a moduleId key
                if (isset($subModuleData['id']) && !isset($subModuleData['moduleId'])) {
                    $subModuleData['moduleId'] = $subModuleData['id'];
                }
                unset($subModuleData['id']);
            }

            $this->compositeCollections[$id] = $this->getCompositeCollectionObject($moduleList);
        }

        return $this->compositeCollections[$id];
    }

    public function updateModules($modulesData)
    {
        $ret = array();

        //filter modules with empty selectors (issue #5763)
        foreach($this as $k => $moduleData) {
            if (empty($moduleData['selector'])) {
                unset($this[$k]);
            }
        }

        foreach ($modulesData as $moduleData) {
            //modules should always have a selector
            if (empty($moduleData['selector'])) {
                continue;
            }
            if (isset($moduleData['id'])) {
                $this->update($moduleData['id'], $moduleData);
            } else {
                $module = $this->add($moduleData);
                if (isset($moduleData['cid'])) {
                    $ret[$moduleData['cid']] = $module['id'];
                }
            }
        }

        return $ret;
    }

    public function removeModules($deletedIds)
    {
        foreach ($this as $k=>$v) {
            if (in_array($v['id'], $deletedIds)) {
                unset($this[$k]);
            }
        }
    }

    public function add($value)
    {
        $this->setDefaults($value);

        if (is_null($value['position'])) {
            $value['position'] = $this->getLatestPosition($value['selector']) + 1;
        }

        return parent::add($value);
    }

    public function replace($el, $newElement)
    {
        foreach ($this as $k=>$v) {
            if ($v == $el) {
                $this[$k] = $newElement;
            }
        }
    }

    public function update($id, $data) {
        if (isset($this->compositeCollections[$id])) {
            unset($this->compositeCollections[$id]);
        }
        return parent::update($id, $data);
    }

    protected function getLatestPosition($selector)
    {
        $position = 0;

        foreach ($this->getBySelector($selector) as $module) {
            if ($module['position'] > $position) {
                $position = $module['position'];
            }
        }

        return $position;
    }

    public function getBySelector($selector)
    {
        $ret = array();
        foreach ($this as $module) {
            if ((empty($selector) && empty($module['selector']))
                || (isset($module['selector']) && $module['selector'] == $selector)) {
                $ret[] = $module;
            }
        }

        uasort($ret, function($a, $b) {
            if (!isset($a['position'])) {
                $a['position'] = 0;
            }
            if (!isset($b['position'])) {
                $b['position'] = 0;
            }
            if ($a['position'] == $b['position']) {
                return ($a['id'] < $b['id']) ? -1 : 1;
            }

            return $a['position'] < $b['position'] ? -1 : 1;
        });

        return $ret;
    }

    protected function setDefaults(&$module)
    {
        if (!is_array($module)) {
            throw new InvalidArgumentException(sprintf('Modules should be arrays containing module data, %s passed', json_encode($module)));
        }

        if (!isset($module['moduleId'])) {
            throw new InvalidArgumentException(sprintf("Module %s doesn't have a moduleId", json_encode($module)));
        }

        if (!isset($module['selector'])) {
            $module['selector'] = '';
        }

        if (!isset($module['position'])) {
            $module['position'] = null;
        }

    }

    public function getTitle()
    {
        return $this->getOneById($this->specialModuleIds['title']);
    }

    public function getSupra()
    {
        return $this->getOneById($this->specialModuleIds['supra']);
    }

    public function getImages()
    {
        return $this->getAllByPackage($this->specialModulePackage['image']);
    }

    public function getImagesIds()
    {

        $ret = array();
        foreach ($this->getImages() as $imageModule) {
            if (isset($imageModule['data'])
                && isset($imageModule['data']['image'])
                && !empty($imageModule['data']['image']['id'])) {
                $ret[] = $imageModule['data']['image']['id'];
            }
        }

        return $ret;
    }

    protected function getImageObject($imageId)
    {
        $imagesRepository = $this->container->get('wf_cms.repository.image');
        return $imagesRepository->find($imageId);
    }

    public function getImagesObjects()
    {
        return $this->_getImagesObjects($this->getImagesIds());
    }
    
    protected function _getImagesObjects($imageIds)
    {
        $ret = array();
        foreach ($imageIds as $imageId) {
            $ret[$imageId] = $this->getImageObject($imageId);
        }

        return $ret;
    }
    
    public function getGalleryImages()
    {
        try {
            $galleryModules = $this->getGallery();
            $galleryCollection = $this->getCompositeCollection(reset($galleryModules));
            if (!empty($galleryCollection)) {
                $galleryImagesIds = $galleryCollection->getImagesIds();

                return $this->_getImagesObjects($galleryImagesIds);
            }
        } catch(\Exception $e) {}
        
        return array();
    }

    public function setRelatedIds($related)
    {
        if (!$related) {
            unset($this->relatedIds);
            return;
        }
        $values = array_fill(0, count($related), 'page');
        $this->relatedIds = array_combine($related, $values);
    }

    public function getRelatedIds()
    {
        if (isset($this->relatedIds)) {
            return $this->relatedIds;
        }

        $relatedModule = $this->getOneById($this->specialModuleIds['related']);
        if (!$relatedModule) {
            return array();
        }
        $relatedModule['id'] = 'related';//set special id to allow the module to live as top module or in any composite module
        $pageLinksCollection = $this->getCompositeCollection($relatedModule);
        if (!$pageLinksCollection) {
            $pageLinksCollection = $this->getCompositeCollectionObject();
        }
        $ret = array();
        foreach($pageLinksCollection as $relatedItem) {
            if (isset($relatedItem['moduleId']) && in_array($relatedItem['moduleId'], $this->specialModuleIds['related_items'])) {
                if (!empty($relatedItem['data']['page']['id'])) {
                    $ret['page-' . $relatedItem['data']['page']['id']] = 'page';
                }
                if (!empty($relatedItem['data']['file']['id'])) {
                    $ret['file-' . $relatedItem['data']['file']['id']] = 'file';
                }
            }
        }

        return $ret;
    }

    protected function getRelatedObject($relatedId, $type)
    {
        $repository = $this->container->get('wf_cms.repository.' . $type);
        return $repository->find($relatedId);
    }

    public function getRelatedObjects()
    {
        $ret = array();
        foreach($this->getRelatedIds() as $relatedId => $relatedType) {
            $ret[$relatedId] = $this->getRelatedObject(str_replace($relatedType . '-', '', $relatedId), $relatedType);
        }

        return $ret;
    }

    public function getRelatedUrl(Page $related)
    {
        $contentRouter = $this->container->get('wf_cms.content_router');
        return $contentRouter->generate($related);
    }

    public function getVideos()
    {
        return $this->getAllByPackage($this->specialModulePackage['video']);
    }

    public function getVideosIds()
    {
        $ret = array();
        $this->walkModulesTree(function($videoModule) use (&$ret){
            if (isset($videoModule['data'])
                && isset($videoModule['data']['video'])
                && !empty($videoModule['data']['video']['id'])) {
                $ret[] = $videoModule['data']['video']['id'];
            }
        });

        return $ret;
    }

    public function getVideosObjects()
    {
        return $this->_getVideosObjects($this->getVideosIds());
    }
    
    protected function _getVideosObjects($videoIds)
    {
        $ret = array();
        foreach($videoIds as $videoId) {
            $ret[] = $this->getVideoObject($videoId);
        }

        return $ret;
    }
    
    public function getGalleryVideos()
    {
        try {
            $galleryModules = $this->getGallery();
            $galleryCollection = $this->getCompositeCollection(reset($galleryModules));
            if (!empty($galleryCollection)) {
                $galleryVideosIds = $galleryCollection->getVideosIds();

                return $this->_getVideosObjects($galleryVideosIds);
            }
        } catch(\Exception $e) {}
        
        return array();
    }

    protected function getVideoObject($id)
    {
        $videosRepository = $this->container->get('wf_cms.repository.video');
        return $videosRepository->find($id);
    }

    public function getAudios()
    {
        return $this->getAllByPackage($this->specialModulePackage['audio']);
    }

    public function getAudiosIds()
    {
        $ret = array();
        foreach($this->getAudios() as $audioModule) {
            if (!empty($audioModule['data']['audio']['id'])) {
                $ret[] = $audioModule['data']['audio']['id'];
            }
        }

        return $ret;
    }

    public function getAudiosObjects()
    {
        $ret = array();
        $ids = $this->getAudiosIds();
        foreach($ids as $audioId) {
            $ret[] = $this->getAudioObject($audioId);
        }

        return $ret;
    }

    protected function getAudioObject($id)
    {
        $audiosRepository = $this->container->get('wf_cms.repository.audio');
        return $audiosRepository->find($id);
    }

    public function getFirstParagraph()
    {
        if ($paragraph = $this->getOneById($this->specialModuleIds['paragraph'])) {
            return $paragraph;
        }

        return $this->getOneByPackage($this->specialModulePackage['paragraph']);
    }

    public function getEpigraph()
    {
        $epigraphModules = $this->getBySelector($this->epigraphSelector);
        if (!empty($epigraphModules)) {
            return array_shift($epigraphModules);
        }

        return $this->getFirstParagraph();
    }

    /**
     * returns all text modules
     */
    public function getTextModules()
    {
        return $this->getAllByIds($this->getTextModulesIds());
    }

    public function getTextHTML()
    {
        $modules = $this->getTextModules();

        if (empty($modules)) {
            return '';
        }

        $ret = '';

        foreach ($modules as $module) {
            if(isset($module['html'])) {
                $ret .= $module['html'];
            }
        }

        return $ret;
    }

    public function getContentHTML()
    {
        $modules = $this->getTextModules();
        if (empty($modules)) {
            return '';
        }

        $ret = '';
        foreach ($modules as $module) {
            if ($module['selector'] == $this->textContentSelector) {
                $ret.= $module['html'];
            }
        }

        return $ret;
    }

    public function getTextModulesIds()
    {
        return array(
            $this->specialModuleIds['paragraph'],
            $this->specialModuleIds['subtitle'],
        );
    }

    /**
     * walks modules and sub-modules and calls the $callback
     * if the callback returns false then the loop is stopped
     *
     * @param callable $callback
     * @return boolean
     */
    public function walkModulesTree($callback)
    {
        foreach($this as $moduleData) {
            $ret = call_user_func_array($callback, array($moduleData));
            if ($ret === false) {//break loop condition
                return false;
            }

            if ($subcollection = $this->getCompositeCollection($moduleData)) {
                $ret = $subcollection->walkModulesTree($callback);
                if ($ret === false) {
                    return false;
                }
            }
        }
    }

    /**
     * @param unknown_type $source
     * @return array of either javascripts or styles ($source) from the modules config for all the owned modules
     */
    protected function getConfigByKey($source)
    {
        $ret = array();

        foreach ($this as $moduleData) {
            $moduleConfig = $this->moduleConfig->getModule($moduleData['moduleId']);
            if (!isset($moduleConfig[$source])) {
                continue;
            }

            if (is_scalar($moduleConfig[$source])) {
                $ret[] = $moduleConfig[$source];
            } else {
                $ret = array_merge($ret, (array)$moduleConfig[$source]);
            }

            if ($subcollection = $this->getCompositeCollection($moduleData)) {
                $ret = array_merge($ret, $subcollection->getJavascripts());
            }
        }

        $ret = array_unique($ret);

        return $ret;
    }

    public function getJavascripts()
    {
        return $this->getConfigByKey('javascripts');
    }

    public function getStyles()
    {
        return $this->getConfigByKey('styles');
    }

    /**
     * returns an array with selectors as keys and array with HTMLs as elements
     */
    public function getHTML()
    {
        $ret = array();

        foreach ($this->getAllSelectors() as $selector) {
            $ret[$selector] = array();

            foreach ($this->getBySelector($selector) as $k=>$moduleData) {
                $ret[$selector][] = $this->getModuleHTML($moduleData);
            }
        }

        return $ret;
    }


    protected function getModuleHTML($moduleData)
    {
        $config = $this->moduleConfig->getModule($moduleData['moduleId']);

        //XXX: the top modules HTML is not affected by children's HTML
        //TODO: should find a way to change this issue
//        $collection = $this->getCompositeCollection($moduleData);
//        if ($collection) {
//            $ret = '';
//            foreach($collection as $k => $submoduleData) {
//                $ret .= $collection->getModuleHTML($submoduleData);
//            }
//
//            return $ret;
//        }

        if (isset($config['route'])) {
            $routeParams = array();
            $all = true;
            if (isset($config['routeParams'])) {
                foreach ($config['routeParams'] as $key => $value) {
                    if (isset($moduleData[$key])) {
                        $routeParams[$key] = $moduleData[$key];
                    }

                    if (isset($moduleData['settings'][$key])) {
                        $routeParams[$key] = $moduleData['settings'][$key];
                    }

                    if (!isset($routeParams[$key]) || is_null($routeParams[$key])) {
                        switch ($key) {
                            case 'categorySlug':
                                if (!is_null($this->category)) {
                                    $routeParams[$key] = $this->category->getSlug();
                                }
                                break;
                            case 'slug':
                                if ($value == '%listingSlug%' && !empty($moduleData['data']['listing']['slug'])) {
                                    $routeParams[$key] = $moduleData['data']['listing']['slug'];
                                    if (!empty($moduleData['settings'])) {
                                        $routeParams['settings'] = json_encode($moduleData['settings']);
                                    }
                                } else if($value == '%pageSlug%' && !empty($moduleData['data']['page']['slug'])) {
                                    $routeParams[$key] = $moduleData['data']['page']['slug'];
                                }
                                break;
                        }
                    }
                    
                    if (!isset($routeParams[$key])) {
                        $routeParams[$key] = null;
                        $all = false;
                    }
                }
            }

            try {
                $url = $this->router->generate($config['route'], $routeParams);
            } catch (RouterException $e) {
                return '';
            }

            try {
                return $this->fragmentHandler->render($url, 'esi');
            } catch (NotFoundHttpException $e) {
                if (!$all) {//error is due to not all params being set in the route (probably something was not set properly)
                    return '';
                }
                
                throw $e;
            }
        }
        if (!isset($moduleData['html'])) {
            return '';
        }
        
        $moduleData['html'] = $this->processDynamicSubmodules($moduleData['html']);
        
        return $moduleData['html'];
    }
    
    protected function processDynamicSubmodules($html)
    {
        if (strpos($html, 'data-dynamic-url') === false) {
            return $html;
        }
        
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $dynamicSubmodules = $crawler->filter('[data-dynamic-url]');
        $fragmentsUrl = array();
        foreach($dynamicSubmodules as $submoduleNode) {
            $document = $submoduleNode->ownerDocument;
            
            $fragmentsUrl[] = $fragmentUrl = 
                $this->kernel->isAdmin() ?
                    $submoduleNode->getAttribute('data-dynamic-url') :
                    str_replace('/view', '', $submoduleNode->getAttribute('data-dynamic-url'));
            $submoduleNode->removeAttribute('data-dynamic-url');
            
            $esiNode = $document->createElement('esi');
            $esiNode->setAttribute('src', $fragmentUrl);
            
            //clean up node
            while ($submoduleNode->hasChildNodes()) {
                $submoduleNode->removeChild($submoduleNode->childNodes->item(0));
            }
            
            $submoduleNode->appendChild($esiNode);
        }
        $html = count($crawler) ? $crawler->html() : '';
        foreach($fragmentsUrl as $fragmentUrl) {
            //make sure the text replaces uses the same encoding as the original one
            $c = new Crawler();
            $c->addContent('<esi src="' . $fragmentUrl . '"></esi>');
            $replace = preg_replace('/<\/?body[^>]*>/', '', $c->html());
            
            $html = str_replace(
                $replace, 
                //'<!-- esi ' . $fragmentUrl . ' -->' . $this->fragmentHandler->render($fragmentUrl, 'esi') . '<!-- endesi ' . $fragmentUrl . ' -->',
                $this->fragmentHandler->render($fragmentUrl, 'esi'),
                $html
            );
        }
        
        return $html;
    }

    protected function getRouteParam($key)
    {
    }

    protected function addTextModule($selector, $moduleId, $text, $tag = 'p', $position = null)
    {
        $dataArray = array('content' => $text);
        $html = sprintf('<%s>%s</%s>', $tag, $text, $tag);

        return $this->add(array(
            'selector' => $selector,
            'moduleId' => $moduleId,
            'data' => $dataArray,
            'html' => $html,
            'position' => $position
        ));
    }

    protected function getImagePaths($image)
    {
        $ret = array();
        if (!empty($this->uploaderMappings) && isset($this->uploaderMappings['image'])) {
            $mapping = $this->uploaderMappings['image'];
            $webRoot = preg_replace('@' . $mapping['uri_prefix'] . '$@', '', $mapping['upload_destination']);

            $fullPath = (string) $image->getImage();
            $relativePath = preg_replace('@^' . $webRoot . '@', '', $fullPath);
            $uriPrefix = $mapping['uri_prefix'];

            $imageName = $image->getImageName();
            $dirPrefix = preg_replace('@^' . $uriPrefix . '@', '', $relativePath);
            $dirPrefix = preg_replace('@' . $imageName . '$@', '', $dirPrefix);
            $dirPrefix = rtrim($dirPrefix, '/');

            $ret = array(
                'webRoot' => $webRoot, // /_PATH_TO_SF_/web
                'fullPath' => $fullPath, // /_PATH_TO_SF_/web/uploads/YYYY/MM/DD/image_name.jpg
                'uri_prefix' => $uriPrefix, // /uploads
                'dir_prefix' => $dirPrefix, // /YYYY/MM/DD
                'name' => $image->getImageName(), // image_name.jpg
                'relativePath' => $relativePath, // /uploads/YYYY/MM/DD/image_name.jpg
            );
        } else {
            throw new InvalidArgumentException('PageEditorModuleCollection doesn\'t have uploadermappings for "image"');
        }

        return $ret;
    }

    protected function getImageRelativeSrc($image)
    {
        $paths = $this->getImagePaths($image);

        return $paths['relativePath'];

    }

    public function setImages($images)
    {
        $mainImageModule = $this->getOneById($this->specialModuleIds['main_image']);

        $isMain = false;
        foreach ($images as $image) {
            if (is_numeric($image)) {//BC
                $image = $this->getImageObject($image);
            }
            $selector = $this->imagesSelector;
            $imageRelativeSrc = $this->getImageRelativeSrc($image);
            $html = sprintf($this->imageHTMLFormat, $imageRelativeSrc);
            if (empty($mainImageModule)) {
                $isMain = true;
                $selector = $this->mainImageSelector;
                $mainImageModule = $this->getOneById($this->specialModuleIds['main_image']);
                $mainImageHTML = $this->getMainImageHTML($image, $imageRelativeSrc);
                if (!empty($mainImageHTML)) {
                    $html = $mainImageHTML;
                }
            }

            $moduleData = $this->add(array(
                'selector' => $selector,
                'moduleId' => $this->specialModuleIds['image'],
                'html' => $html,
                'data' => array(
                    'image' => array(
                        'id' => $image->getId()
                    )
                )
            ));

            if ($isMain) {
                $this->mainImageAdded($image, $moduleData);
            }

            $isMain = false;
        }
    }

    protected function getRelatedData($related)
    {
        $relatedLinksCollection = $this->getCompositeCollectionObject();
        $relatedLinksHTML = '';
        foreach($related as $relatedPage) {
            /* @var $$page \Wf\Bundle\CmsBaseBundle\Entity\Page */
            if (is_array($relatedPage)) {
                $page = $this->getRelatedObject($relatedPage['id']);
            } else {
                $page = $relatedPage;
            }

            $linkCollection = $this->getCompositeCollectionObject();
            $linkHTML = sprintf($this->pageLinkHTMLFormat, $page->getPreviewUrl(), $page->getTitle());
            $linkCollection->add(array(
                'moduleId' => $this->specialModuleIds['related_page_inner'],
                'html' => $linkHTML,
                'data' => array(
                    'content' => $page->getTitle(),
                ),
            ));
            $pageLinkHTML = sprintf($this->pageLinkItemHTMLFormat, $linkHTML);
            $relatedLinksCollection->add(array(
                'moduleId' => $this->specialModuleIds['related_page'],
                'html' => $pageLinkHTML,
                'data' => array(
                    'collection' => $linkCollection->toArray(),
                    'page' => $page->getRelatedData(),
                ),
            ));
            $relatedLinksHTML .= $pageLinkHTML;
        }

        return array(
            'selector' => $this->relatedSelector,
            'moduleId' => $this->specialModuleIds['related'],
            'html' => sprintf($this->relatedHTMLFormat, $relatedLinksHTML),
            'data' => array(
                'collection' => $relatedLinksCollection->toArray(),
            ),
        );
    }

//    public function setRelated($related)
//    {
//        $this->add($this->getRelatedData($related));
//    }

    protected function mainImageAdded($image, $moduleData)
    {
    }

    protected function getMainImageHTML($image, $imageSrc)
    {
    }

    public function setVideos($videos)
    {
        $mainVideoModule = $this->getOneById($this->specialModuleIds['main_video']);

        foreach ($videos as $videoId) {
            $selector = $this->videosSelector;
            if (empty($mainVideoModule)) {
                $selector = $this->mainVideoSelector;
                $mainVideoModule = $this->getOneById($this->specialModuleIds['main_video']);
            }

            $this->add(array(
                'selector' => $selector,
                'moduleId' => $this->specialModuleIds['video'],
                'data' => array(
                    'content' => $title
                )
            ));
        }
    }

    public function addParagraphs($paragraphs)
    {
        foreach ($paragraphs as $paragraph) {
            $this->addParagraph($paragraph);
        }
    }

    public function addParagraph($paragraph)
    {
        if ($this->roledModuleExists('paragraph')) {
            return $this->addRoledModule('paragraph', $paragraph);
        }
        
        $this->add(array(
            'selector' => $this->textContentSelector,
            'html' => sprintf($this->paragraphHTMLFormat, $paragraph),
            'moduleId' => $this->specialModuleIds['paragraph'],
            'data' => array(
                'content' => $paragraph
            )
        ));
    }


    public function getParagraphs()
    {
       $modules = $this->getTextModules();
       if (empty($modules)) {
           return '';
       }

       $ret = '';
       foreach ($modules as $module) {
           if ($module['selector'] == $this->textContentSelector) {
               $ret.= $module['html'];
           }
       }

       return $ret;
    }

    public function addSubtitle($subtitle)
    {
            $this->add(array(
                'selector' => $this->textContentSelector,
                'html' => sprintf($this->subtitleHTMLFormat, $subtitle),
                'moduleId' => $this->specialModuleIds['subtitle'],
                'data' => array(
                    'content' => $subtitle
                )
            ));
    }

    public function addTextContent($textContent)
    {
        foreach ($textContent as $textModule) {
            if (!isset($textModule['module']) || empty($textModule['module'])) {
                $textModule['module'] = 'paragraph';
            }

            $method = sprintf('add%s', ucfirst($textModule['module']));
            if (!method_exists($this, $method)) {
                throw new \InvalidArgumentException(sprintf('You\'ve tried to add an invalid module %s', $textModule['module']));
            }

            call_user_func(array($this, $method), $textModule['text']);
        }
    }
    
    public function setSignature($signature, $author)
    {
        $module = $this->getRoledModule('signature');
        if (empty($signature)) {
            $this->setRoledModule('signature', $author, $module['position']);
        } else if (!empty($signature)) {
            $this->setRoledModule('signature', $signature, $module['position']);
        }
    }
    
    public function setPublishedAt($publishedAt)
    {
        $module = $this->getRoledModule('published_at');
        if (!empty($module) && (empty($module['data']['content']) || !empty($module['automatic']))) {
            $this->setRoledModule('published_at', $publishedAt, isset($module['position']) ? $module['position'] : 0);
            $module = $this->getRoledModule('published_at');
            if (empty($module)) {
                return;
            }
            //mark this module as automatic so it's value can be updated (issue #12244)
            $key = $this->keyOf($module);
            $module['automatic'] = true;
            $this[$key] = $module;
        }
    }
    
    public function setFirstPublishedAt($firstPublishedAt)
    {
        $module = $this->getRoledModule('first_published_at');
        if (!empty($module) && (empty($module['data']['content']) || !empty($module['automatic']))) {
            $this->setRoledModule('first_published_at', $firstPublishedAt, isset($module['position']) ? $module['position'] : 0);
            $module = $this->getRoledModule('first_published_at');
            if (empty($module)) {
                return;
            }
            //mark this module as automatic so it's value can be updated (issue #12244)
            $key = $this->keyOf($module);
            $module['automatic'] = true;
            $this[$key] = $module;
        }
    }

    public function __call($methodName, $arguments)
    {
        //auto-register getTitleHTML and so on
        if (preg_match('@^get.*?Content$@', $methodName)) {
            $getter = preg_replace('@Content$@', '', $methodName);
            $field = preg_replace('@^get@', '', $getter);
            if (method_exists($this, $getter)) {
                $module = call_user_func_array(array($this, $getter), $arguments);

                if (empty($module)) {
                    return '';
                }

                return $this->getModuleContent($module);
            } elseif ($this->roledModuleExists(Inflector::tableize($field))) {
                $module = $this->getRoledModule(Inflector::tableize($field));
                if (empty($module)) {
                    return '';
                }
                
                return $this->getModuleContent($module);
            }
        } elseif (preg_match('@^set(.*)@', $methodName, $matches)) {
            //match setTitle/setSupra/setParagraphs
            $role = $matches[1];
            $role = Inflector::tableize($role);
            $singularRole = Inflector::singularize($role);

            if ($role != $singularRole) {
                $method = 'setRoledModules';
                $role = $singularRole;
            } else {
                $method = 'setRoledModule';
            }

            if ($this->roledModuleExists($role)) {
                array_unshift($arguments, $role);

                return call_user_func_array(array($this, $method), $arguments);
            }

        } elseif (preg_match('@^add(.*)@', $methodName, $matches)) {
            //match addTitle/addSupra/addParagraph
            $role = $matches[1];
            $role = Inflector::tableize($role);
            $method = 'addRoledModule';

            if ($this->roledModuleExists($role)) {
                array_unshift($arguments, $role);

                return call_user_func_array(array($this, $method), $arguments);
            }
        }

        throw new \Exception(sprintf('Call to undefined method %s on PageEditorModuleCollection', $methodName));
    }

    protected function getModuleContent($module)
    {
        if (isset($module['data'])) {
            $data = $module['data'];
            $keys = array('content');

            foreach ($keys as $key) {
                if (isset($data[$key])) {
                    return $data[$key];
                }
            }
        }

        return '';

    }

    protected function sortPositions()
    {
        $selectors = $this->getAllSelectors();

        foreach ($selectors as $selector) {
            $this->sortSelectorPositions($selector);
        }
    }

    protected function sortSelectorPositions($selector)
    {
        $lastPosition = 0;
        $modules = $this->getBySelector($selector);

        foreach ($modules as $k => &$moduleData) {
            if (!isset($moduleData['position'])) {
                $moduleData['position'] = 0;
            }
            if (isset($lastModule) && $moduleData['position'] <= $lastModule['position']) {
                $moduleData['position'] = $lastModule['position'] + 1;
            }

            $lastModule = $moduleData;
        }

        $this->replaceSelectorModules($selector, $modules);
    }

    protected function replaceSelectorModules($selector, $modules)
    {
        foreach ($this as $k => $moduleData) {
            if (isset($moduleData['selector']) && $moduleData['selector'] == $selector) {
                unset($this[$k]);
            }
        }

        foreach ($modules as $moduleData) {
            $this[] = $moduleData;
        }
    }
    
    public function removeSelectorModules($selector)
    {
        $this->replaceSelectorModules($selector, array());
    }
    
    public function removeParagraphs()
    {
        $moduleConfig = $this->getRoledModuleConfig('paragraph');
        if (!empty($moduleConfig['selector'])) {
            $this->removeSelectorModules($moduleConfig['selector']);
        }
    }

    public function toArray()
    {
        $this->sortPositions();

        return parent::toArray();
    }

    public function setUploaderMappings($uploaderMappings)
    {
        $this->uploaderMappings = $uploaderMappings;
    }

    protected function setRoledModule($role, $content, $position = null)
    {
        if ($this->template !== 'default') {
            error_log('Only the default template supported. Trying to set role ' . $role . ' for template ' . $this->template);
            return;
        }

        $module = $this->getRoledModule($role);

        if (!$module) {
            $this->addRoledModule($role, $content, $position);
        } else {
            $this->replaceRoledModule($role, $content, $position, !empty($module['id']) ? $module['id'] : null);
        }
    }

    protected function setRoledModules($role, $contents, $position = 0)
    {
        $moduleConfig = $this->getRoledModuleConfig($role);
        $existingModules = $this->getAllById($moduleConfig['moduleId']);
        foreach ($existingModules as $key=>$existingModule) {
            if ($existingModule['selector'] == $moduleConfig['selector']) {
                $this->delete($existingModule['id']);
            }
        }

        foreach ($contents as $content) {
            $this->addRoledModule($role, $content, $position++);
        }
    }

    protected function getRoledModuleConfig($role)
    {
        $this->loadTemplateConfig();

        return isset($this->templateConfig['modules'][$role]) ? $this->templateConfig['modules'][$role] : null;
    }

    protected function roledModuleExists($role)
    {
        $this->loadTemplateConfig();

        return isset($this->templateConfig['modules'][$role]);
    }

    public function getRoledModule($role)
    {
        $moduleConfig = $this->getRoledModuleConfig($role);

        foreach($this->getBySelector($moduleConfig['selector']) as $module) {
            if ($module['moduleId'] == $moduleConfig['moduleId']) {
                return $module;
            }
        }

        return null;
    }

    protected function addRoledModule($role, $content, $position = null)
    {
        $moduleData = $this->getRoledModuleData($role, $content, $position);
        
        if (!empty($moduleData)) {
            return $this->add($moduleData);
        }
    }

    protected function replaceRoledModule($role, $content, $position = null, $id = null)
    {
        $moduleConfig = $this->getRoledModuleConfig($role);

        $module = $this->getRoledModule($role);
        if (empty($module)) {//if there is no module, create one
            $module = $this->addRoledModule($role, $content, $position);
        }
        
        if (empty($module['selector'])) {//module is nowhere on the page; skip it
            return;
        }
        
        if ($module['selector'] != $moduleConfig['selector']) {//make sure the module has the same selector otherwise we risk having a module with the same id be taken from another selector
            $selectorModules = (array)$this->getBySelector($moduleConfig['selector']);
            $selectorModule = null;
            foreach($selectorModules as $moduleData) {
                if ($moduleData['moduleId'] == $moduleConfig['moduleId']) {
                    $selectorModule = $moduleData;
                    break;
                }
            }
            
            if ($selectorModule) {//if we have a module with this id in the right selector use this
                $module = $selectorModule;
            } else {//otherwise add a new module
                $module = $this->addRoledModule($role, $content, $position);
            }
        }

        $key = $this->keyOf($module);
        $this[$key] = $this->getRoledModuleData($role, $content, $position, $id);

        return $this[$key];
    }

    protected function getRoledModuleData($role, $content, $position = null, $id = null)
    {
        $moduleConfig = $this->getRoledModuleConfig($role);
        if (empty($moduleConfig)) {
            return array();
        }

        $htmlFormat = $moduleConfig['html'];

        if (strpos($htmlFormat, '%content$s') !== false) {
            //text module
            $dataArray = $this->getTextModuleData($content);
        } else {
            $dataArray = $this->getImageModuleData($content);
        }

        $html = sprintfn($htmlFormat, $dataArray);
        if (is_null($position)) {
            $position = count($this->getBySelector($moduleConfig['selector']));
        }

        return array(
            'selector' => $moduleConfig['selector'],
            'moduleId' => $moduleConfig['moduleId'],
            'data' => $this->replaceData($moduleConfig['data'], $dataArray),
            'html' => $html,
            'position' => $position,
            'id' => $id,
        );
    }

    protected function getTextModuleData($content)
    {
        return array(
            'content' => $content
        );
    }

    protected function getImageModuleData(Image $image)
    {
        $imagePaths = $this->getImagePaths($image);
        $imagePrefix = $imagePaths['dir_prefix'];

        return array(
            'image_id' => $image->getId(),
            'image_prefix' => $imagePrefix,
            'image_name' => $image->getImageName(),
            'caption' => $image->getDescription()
        );
    }

    protected function replaceData($template, $data)
    {
        foreach ($template as $key=>$value) {
            if (is_array($value)) {
                $template[$key] = $this->replaceData($value, $data);
            } elseif (is_string($value)) {
                $template[$key] = sprintfn($value, $data);
            }
        }

        return $template;
    }

    protected $templateConfig;
    protected function loadTemplateConfig()
    {
        if (!is_null($this->templateConfig)) {
            return $this->templateConfig;
        }

        $cmsConfiguration = $this->container->get('wf_cms.cms_configuration');
        $this->templateConfig = $cmsConfiguration->getTemplateConfig();

        return $this->templateConfig;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    public function getExternalVideoModules()
    {
        $ret = array();
        foreach ($this as $moduleData) {
            if (isset($moduleData['source']) && isset($moduleData['settings']['mediaId'])) {
                $ret[] = $moduleData;
            }
        }

        return $ret;
    }

    public function getMainImage()
    {
        if (!$module = $this->getRoledModule('image')) {
            $module = $this->getRoledModule('image_captioned');
        }

        if (isset($module['data']['image']) && isset($module['data']['image']['id'])) {
            return $this->getImageObject($module['data']['image']['id']);
        }

        $imageObjects = $this->getImagesObjects();
        if (is_array($imageObjects)) {
            return array_shift($imageObjects);
        }

        return null;
    }
}
