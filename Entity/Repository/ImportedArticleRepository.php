<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * ImportedArticleRepository
 */
class ImportedArticleRepository extends EntityRepository
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
        if (!$importedArticle = $this->findOneBySourceId($data['source_id'])) {
            return $this->save($data);
        }

        return $importedArticle;
    }

    public function getImportedArticleInstance()
    {
        $importedArticleClass = $this->getClassName();
        return new $importedArticleClass();
    }

    public function findOneBySourceId($sourceId) {
        $query = $this->getBaseQB()->where("i.sourceId = '{$sourceId}'")->setMaxResults(1)->getQuery();
        if ( ($importedArticle = $query->getResult()) ) {
            return $importedArticle[0];
        }
        
        return false;
    }
    
    /**
     * @param  array $data
     * @return \Wf\Bundle\CmsBaseBundle\Entity\ImportedArticle
     */
    public function save($data)
    {
        $importedArticle = $this->getImportedArticleInstance();
        
        if (isset($data['title'])) {
            $importedArticle->setTitle($data['title']);
        }
        if (isset($data['source_id'])) {
            $importedArticle->setSourceId($data['source_id']);
        }
        if (isset($data['text']) && count($data['text'])) {
            $importedArticle->setText($data['text']);
        }
        if (isset($data['images']) && count($data['images'])) {
             $importedArticle->setImages($data['images']);
        }
        if (isset($data['galleries']) && count($data['galleries'])) {
             $importedArticle->setGalleries($data['galleries']);
        }
        
        $em = $this->getEntityManager();
        $em->persist($importedArticle);
        $em->flush();
        
        return $importedArticle;
    }
}