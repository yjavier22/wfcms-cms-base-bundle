<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Wf\Bundle\CmsBaseBundle\Entity\Page;

use Wf\Bundle\CmsBaseBundle\Entity\Article;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * ArticleRepository
 */
class ArticleRepository extends EntityRepository
{
    public function getHomepageSlider($limit = 8)
    {
        return $this->getLatestItems(null, $limit);
    }
    
    public function getHomepageMain()
    {
        $articles = $this->getLatestItems(null, 15);
        
        return array_pop($articles);
    }
    
    public function getByCategory(Category $category)
    {
        return $this->getByCategoryQB($category)
            ->getQuery()
                ->getResult()
            ;
    }
    
    public function getByCategoryQB(Category $category)
    {
        return $this->getBaseQB()
            ->andWhere('it.category = :category')
                ->setParameter('category', $category->getId())
            ;
    }
    
    /**
     * @return QueryBuilder $qb
     */
    public function getBaseQB($onlyActive = true)
    {
        $qb = $this->createQueryBuilder('it')
            ->add('orderBy', 'it.createdAt DESC')
            ;
            
        if ($onlyActive) {
            $qb
                ->andWhere('it.status = :status')
                    ->setParameter('status', Article::STATUS_PUBLISHED)
                ;
        }
        
        return $qb;
    }

    public function getLatestItems(Category $category = null, $limit = 5)
    {
        return $this->getLatestItemsQB($category, $limit)
            ->getQuery()
                ->getResult();
    }

    protected function getLatestItemsQB($category = null, $limit = 5, $onlyActive = true)
    {
        if (empty($category)) {
            $qb = $this->getBaseQB($onlyActive);
        } else {
            $qb = $this->getByCategoryQB($category);
        }

        if ($limit) {
            $qb
                ->setMaxResults($limit)
                ;
        }
            
        return $qb;
    }
    
    public function getByPage(Page $page)
    {
        $ret = $this->getByPageQB($page)
            ->getQuery()
                ->getResult();
                
        if (empty($ret)) {
            return null;
        }
        
        return array_pop($ret);
    }
    
    protected function getByPageQB($page)
    {
        $qb = $this->getBaseQB($onlyActive = false);
        
        $qb
            ->andWhere('it.page = :page')
                ->setParameter('page', $page->getId())
            ;
            
        return $qb;
    }

}