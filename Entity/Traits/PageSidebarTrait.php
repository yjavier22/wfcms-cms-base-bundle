<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 */
trait PageSidebarTrait
{
    public function getPageType()
    {
        return self::TYPE_SIDEBAR;
    }
}

