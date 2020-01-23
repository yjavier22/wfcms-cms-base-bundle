<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * ImageRepository
 */
class ImageRepository extends EntityRepository
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

    /**
     *
     * @return \Wf\Bundle\CmsBaseBundle\Entity\Repository\imageClass
     */
    public function getImageInstance()
    {
        $imageClass = $this->getClassName();
        return new $imageClass();
    }

    public function import($data)
    {
        if (!$image = $this->findOneBySourceId($data['source_id'])) {
            return $this->save($data);
        }
        
        return $image;
    }

    public function findOneBySourceId($sourceId) {
        $query = $this->getBaseQB()->where("i.sourceId = '{$sourceId}'")->setMaxResults(1)->getQuery();
        if ( ($image = $query->getResult()) ) {
            return $image[0];
        }
        
        return false;
    }
    
    /**
     * @param  array $data
     * @return \Wf\Bundle\CmsBaseBundle\Entity\Image
     */
    public function save($data)
    {
        $image = $this->getImageInstance();
        
        if (isset($data['title'])) {
            $image->setTitle($data['title']);
        }
        if (isset($data['image'])) {
            $image->setImage($data['image']);
        }
        if (isset($data['source_id'])) {
            $image->setSourceId($data['source_id']);
        }
        if (isset($data['fields'])) {
            $image->setFields($data['fields']);
        }
        
        $em = $this->getEntityManager();
        $em->persist($image);
        $em->flush();
        
        return $image;
    }
}