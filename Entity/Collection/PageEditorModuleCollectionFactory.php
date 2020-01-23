<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Collection;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wf\Bundle\CmsBaseAdminBundle\Configuration\EditorModulesConfiguration as ModuleConfig;

/**
 * @author gk
 */
class PageEditorModuleCollectionFactory
{
    protected $pageEditorModuleCollectionClass;

    /**
     * The service container - used to get module renderer services
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Mappings for the uploader used in the app. Useful for determining
     * assets' relative path
     * @var array
     */
    protected $uploaderMappings;

    public function __construct(ModuleConfig $config, $pageEditorModuleCollectionClass, ContainerInterface $container)
    {
        $this->config = $config;
        $this->pageEditorModuleCollectionClass = $pageEditorModuleCollectionClass;
        $this->container = $container;
    }

    public function setUploaderMappings($uploaderMappings)
    {
        $this->uploaderMappings = $uploaderMappings;
    }

    public function create($modules = array())
    {
        if (is_null($modules)) {
            $modules = array();
        }

        if ($modules instanceof Collection) {
            $modules = $modules->toArray();
        }

        $ret = new $this->pageEditorModuleCollectionClass($modules);
        $ret->setModuleConfig($this->config);
        $ret->setContainer($this->container);
        $ret->setUploaderMappings($this->uploaderMappings);

        return $ret;
    }

}