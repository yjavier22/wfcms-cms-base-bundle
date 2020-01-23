<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * GalleryRepository
 */
class GalleryRepository extends EntityRepository
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

    public function import($data)
    {
        if (!$gallery = $this->findOneBySourceId($data['source_id'])) {
            return $this->save($data);
        }

        return $gallery;
    }

    public function getGalleryInstance()
    {
        $galleryClass = $this->getClassName();
        return new $galleryClass();
    }

    public function findOneBySourceId($sourceId) {
        $query = $this->getBaseQB()->where("i.sourceId = '{$sourceId}'")->setMaxResults(1)->getQuery();
        if ( ($gallery = $query->getResult()) ) {
            return $gallery[0];
        }

        return false;
    }

    /**
     * @param  array $data
     * @return \Wf\Bundle\CmsBaseBundle\Entity\Gallery
     */
    public function save($data)
    {
        $gallery = $this->getGalleryInstance();

        if (isset($data['title'])) {
            $gallery->setTitle($data['title']);
        }
        if (isset($data['source_id'])) {
            $gallery->setSourceId($data['source_id']);
        }
        if (isset($data['published_at'])) {
            $gallery->setPublishedAt($data['published_at']);
        }
        if (isset($data['images']) && count($data['images'])) {
             $gallery->setImages($data['images']);
        }

        $em = $this->getEntityManager();
        $em->persist($gallery);
        $em->flush();

        return $gallery;
    }
}