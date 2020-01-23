<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Wf\Bundle\CmsBaseBundle\Entity\Page;

/**
 */
class BoardTagRepository extends PageRepository
{
    /**
     * @see Wf\Bundle\CmsBaseBundle\Entity\Repository.PageRepository::getBaseQB()
     */
    public function getBaseQB($onlyActive = true)
    {
        $qb = parent::getBaseQB($onlyActive);
        $qb->byType(Page::TYPE_TAG);
        return $qb;
    }


}