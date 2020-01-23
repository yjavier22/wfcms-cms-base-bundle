<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * AudioRepository
 */
class AudioRepository extends EntityRepository
{
    public function getMediaByIds($ids)
    {
        $qb = $this->getBaseQB();

        return $qb
            ->where($qb->expr()->in('i.id', ':ids'))
            ->setParameter(':ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getLatestQB()
    {
        return $this->getBaseQB();
    }
    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getBaseQB()
    {
        $qb = $this->createQueryBuilder('i')
            ->add('orderBy', 'i.createdAt DESC')
            ;

        return $qb;
    }
}