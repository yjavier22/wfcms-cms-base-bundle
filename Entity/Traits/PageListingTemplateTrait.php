<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 */
trait PageListingTemplateTrait
{
    public function getPageType()
    {
        return self::TYPE_LISTING_TEMPLATE;
    }

    public function getRenderer()
    {
        throw new \Exception('Listing templates should not be rendered!');
    }

    public function setSettings($settings)
    {
        if (!isset($settings['elements'])) {
            $settings['elements'] = array();
        }
        $settings['elements'] = array_unique($settings['elements']);

        return parent::setSettings($settings);
    }


    public function __toString()
    {
        if ($this->getTitle()) {
            return $this->getTitle();
        }

        return '';
    }

}
