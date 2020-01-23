<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * ImageRepository
 */
class VideoRepository extends EntityRepository
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
        $qb = $this->createQueryBuilder('i')
            ->add('orderBy', 'i.createdAt DESC')
            ;

        return $qb;
    }

    public function getMediaByIds($ids)
    {
        $qb = $this->getBaseQB();

        $results = $qb
            ->where($qb->expr()->in('i.id', ':ids'))
            ->setParameter(':ids', $ids)
            ->getQuery()
            ->getResult();

        $videos = array();
        foreach($results as $video) {
            $videos[$video->getId()] = $video;
        }

        // return list of videos sorted by original ids order
        return $this->sortArrayByArray($videos, $ids);
    }

    function sortArrayByArray($array,$orderArray)
    {
        $ordered = array();
        foreach($orderArray as $key) {
            if(array_key_exists($key,$array)) {
                $ordered[$key] = $array[$key];
                unset($array[$key]);
            }
        }
        return $ordered + $array;
    }
}