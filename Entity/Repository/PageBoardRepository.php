<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Wf\Bundle\CmsBaseBundle\Entity\Category;
use Doctrine\ORM\QueryBuilder;

/**
 * PageBoardRepository
 */
class PageBoardRepository extends PageRepository
{
    /**
     * @return QueryBuilder $qb
     */
    public function getBaseQB($onlyActive = true)
    {
        $qb = parent::getBaseQB($onlyActive);
        $qb->byType(Page::TYPE_BOARD);

        return $qb;
    }

    public function findByCategory($category)
    {
        $qb = $this->getBaseQB()
            ->byCategory($category)
            ;

        return $qb->getResults();
    }

}