<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 */
trait PageMetatagsTrait
{
    public function getPageType()
    {
        return self::TYPE_METATAGS;
    }

    public function getRenderer()
    {
        return 'metatags';
    }
}

