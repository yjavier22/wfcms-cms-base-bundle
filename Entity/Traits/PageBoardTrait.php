<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 */
trait PageBoardTrait
{
    public function getPageType()
    {
        return self::TYPE_BOARD;
    }

    protected function syncModules()
    {
        parent::syncModules();

        $modulesCollection = $this->getModulesCollection();

        $modulesCollection->setRelatedIds($this->getEmbeddedPages());
        $related = $modulesCollection->getRelatedObjects();

        $this->_setRelated($related);
    }

    public function getEmbeddedPages() {
        $pages = array();
        $this->getModulesCollection()->walkModulesTree(function($moduleData) use (&$pages) {
            if (isset($moduleData['data']['page']['id'])) {
                $pages[] = $moduleData['data']['page']['id'];
            }
        });

        return array_unique($pages);
    }

    public function getBoardSlugs()
    {
        return $this->getModulesCollection()->getBoardSlugs();
    }

}

