<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Wf\Bundle\CmsBaseBundle\Entity\Category;

/**
 */
class BoardSidebarRepository extends PageRepository
{
    /**
     * @see Wf\Bundle\CmsBaseBundle\Entity\Repository.PageRepository::getBaseQB()
     */
    public function getBaseQB($onlyActive = true)
    {
        $qb = parent::getBaseQB($onlyActive);
        $qb->byType(Page::TYPE_SIDEBAR);
        return $qb;
    }

    public function getOneByCategory(Category $category)
    {
        $qb = $this->getBaseQb(false)
            ->byCategory($category, null, false)
            ;

        return $qb->getSingleResult();
    }

}