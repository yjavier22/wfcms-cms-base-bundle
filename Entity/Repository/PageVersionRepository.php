<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Doctrine\ORM\EntityRepository;

/**
 * PageVersionRepository
 */
class PageVersionRepository extends EntityRepository
{
    public function setPageRepository($pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }

    /**
     * @param Page|int $page either the Page object or the id of such an object
     */
    public function getLatestVersion($page)
    {
        $qb = $this->getBaseQB();
        $qb->andWhere('pv.page = :page')
           ->setParameter('page', $page->getId())
           ->setMaxResults(1)
           ;

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

    public function getVersions($page, $limit = 5)
    {
        $qb = $this->getBaseQB();
        $qb->andWhere('pv.page = :page')
           ->setParameter('page', $page->getId())
           ;
        if (!empty($limit)) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()
            ->getResult()
            ;
    }

    public function getPublishedVersions($page, $limit = 5)
    {
        return $qb
           ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
            ;
    }

    public function getBaseQB($onlyActive = true)
    {
        return $this->createQueryBuilder('pv')
            ->add('orderBy', 'pv.versionNo DESC')
            ;
    }

    public function getPublishedQB($page)
    {
        $qb = $this->getBaseQB();
        $qb->andWhere('pv.page = :page')
           ->setParameter('page', $page->getId())
           ->andWhere('pv.publishedAt IS NOT NULL')
           ;

       return $qb;
    }

    public function getFindByPagesQb(array $pages)
    {
        $pagesIds = array();
        foreach($pages as $page) {
            $pagesIds[] = $page->getId();
        }

        $qb = $this->createQueryBuilder('pv');
        $qb->andWhere($qb->expr()->in('pv.page', ':ids'))
                ->setParameter('ids', $pagesIds);

        return $qb;
    }

    public function findByPages(array $pages)
    {
        $qb = $this->getFindByPagesQb($pages);
        return $qb->getQuery()->getResult();
    }

    public function getByPagesAfterPublishedAt(array $pages, \DateTime $publishedAt)
    {
        $qb = $this->getFindByPagesQb($pages);
        $qb->andWhere($qb->getRootAlias() . '.publishedAt >= :publishedAt')
                ->setParameter('publishedAt', $publishedAt);

        return $qb->getQuery()->getResult();
    }

    public function getByPagesBeforePublishedAt(array $pages, \DateTime $publishedAt)
    {
        $qb = $this->getFindByPagesQb($pages);
        $qb->andWhere($qb->getRootAlias() . '.publishedAt <= :publishedAt')
            ->setParameter('publishedAt', $publishedAt);

        return $qb->getQuery()->getResult();
    }
}