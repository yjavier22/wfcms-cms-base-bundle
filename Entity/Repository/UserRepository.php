<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{

    public function findOneBySlug($slug)
    {
        $object = parent::findOneBy(array('slug' => $slug));

        if (!$object
            || $object->getSlug() != $slug //fixes #11071 - the slug in DB doesn't have diacritics but MySQL matches also with diacritics
        ) {
            return null;
        }

        return $object;
    }

    public function search($term, $page, $rpp = 10)
    {
        $qb = $this->createQueryBuilder('t')
            ->add('orderBy', 't.username')
            ;

        $qb->andWhere(
            $qb->expr()->like('t.username',
                $qb->expr()->literal($term . '%')
                )
            )
            ->setFirstResult(($page - 1) * $rpp)
            ->setMaxResults($rpp)
            ;

        return $qb->getQuery()->getResult();
    }
}