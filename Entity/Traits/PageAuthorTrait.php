<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 */
trait PageAuthorTrait
{
    public function getPageType()
    {
        return self::TYPE_AUTHOR;
    }
}

