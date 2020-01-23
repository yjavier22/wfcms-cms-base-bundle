<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMSS;

/**
 */
trait PageListingTrait
{
    /**
     * @ORM\ManyToOne(targetEntity="Wf\Bundle\CmsBaseBundle\Entity\PageListingTemplate")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("Wf\Bundle\CmsBaseBundle\Entity\PageListingTemplate")
     */
    protected $parent;

    public function getPageType()
    {
        return self::TYPE_LISTING;
    }

    public function getRenderer()
    {
        return self::RENDERER_LISTING;
    }

    public function setSettings($settings)
    {
        if (!isset($settings['elements'])) {
            $settings['elements'] = array();
        }
        $settings['elements'] = json_encode(array_unique($settings['elements']));

        if ($parent = $this->getParent()) {
            $parentSettings = array_merge($parent->getSettings());
            $parentSettings['elements'] = json_encode($parentSettings['elements']);
            $settings = array_diff($settings, $parentSettings);
        }

        return parent::setSettings($settings);
    }

    public function getSettings()
    {
        $settings = parent::getSettings();
        $settings['elements'] = !empty($settings['elements']) ? json_decode($settings['elements'], true) : array();

        if ($this->getParent()) {
            $parentSettings = $this->getParent()->getSettings();
            $mergedSettings = array_merge($parentSettings, $settings);
            if (empty($mergedSettings['elements'])) {
                $mergedSettings['elements'] = $parentSettings['elements'];
            }
        } else {
            $mergedSettings = $settings;
        }

        if (!empty($mergedSettings['elements_ordered'])) {
            $ordered = explode(',', $mergedSettings['elements_ordered']);
            $mergedSettings['elements'] = array_intersect($ordered, $mergedSettings['elements']);
        }

        return $mergedSettings;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
        $this->setSettings($this->getSettings());
    }
    
    public function setElements($elements)
    {
        $settings = $this->getSettings();
        $settings['elements'] = $elements;
        $settings['elements_ordered'] = join(',', $elements);
        $this->setSettings($settings);
    }

    public function setDisplayType($displayType)
    {
        $settings = $this->getSettings();
        $settings['displayType'] = $displayType;
        if ($displayType == 'grid' && empty($settings['gridColumns'])) {
            $settings['gridColumns'] = 3;
        }
        $this->setSettings($settings);
    }

}

