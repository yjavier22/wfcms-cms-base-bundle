<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Wf\Bundle\CmsBaseBundle\Entity\Repository\TreeRepository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * MenuRepository
 */
class MenuRepository extends TreeRepository
{

    public function getMenuItems($menuSlug, $onlyActive = true)
    {
        $node = $this->findOneBySlug($menuSlug);

        $qb = $this->getNodesHierarchyQueryBuilder($node);

        if($onlyActive) {
            $qb->andWhere('node.active = :active')
                ->setParameter('active', true);

            // only children with active parents
            $qb->innerJoin('node.parent', 'p', 'WITH', 'node.parent = p.id')
                ->andWhere('p.active = true');
        }

        $items = $qb
            ->getQuery()
            ->getArrayResult();

        $items = $this->buildTree($items);

        return $items;
    }

}