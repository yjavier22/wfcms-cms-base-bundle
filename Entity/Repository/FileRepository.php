<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * FileRepository
 */
class FileRepository extends EntityRepository
{
    protected function getLatestQB()
    {
        return $this->getBaseQB();
    }
    /**
     * @return QueryBuilder $qb
     */
    public function getBaseQB()
    {
        $qb = $this->createQueryBuilder('f')
            ->add('orderBy', 'f.createdAt DESC')
            ;

        return $qb;
    }
}