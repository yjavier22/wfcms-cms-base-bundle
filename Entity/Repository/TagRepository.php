<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Wf\Bundle\CmsBaseBundle\Sitemap\SitemapCapableInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Wf\Bundle\CmsBaseBundle\Entity\Page;
/**
 * TagRepository
 */
class TagRepository extends EntityRepository implements SitemapCapableInterface
{
    public function getLatest($page, $rpp = 10, $tagType)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->orderBy('t.updatedAt', 'desc')
            ->setFirstResult(($page - 1) * $rpp)
            ->setMaxResults($rpp)
            ;

        $qb = $this->byType($qb, $tagType);

        return $qb->getQuery()->getResult();
    }

    public function search($term, $page, $rpp = 10, $tagType = NULL)
    {
        $qb = $this->getBaseQB();
        $qb->andWhere(
            $qb->expr()->like('t.title',
                $qb->expr()->literal($term . '%')
                )
            )
            ->setFirstResult(($page - 1) * $rpp)
            ->setMaxResults($rpp)
            ;

        // if ($tagType !== NULL) {
            $qb = $this->byType($qb, $tagType);
        // }


        return $qb->getQuery()->getResult();
    }

    public function byType($qb, $type)
    {
        if ($type) {
            $qb->andWhere($qb->expr()->eq('t.type', ':type'))
                ->setParameter('type', $type);
        } else {
            $qb->andWhere($qb->expr()->isNull('t.type'));
        }

        return $qb;
    }

    public function batchCreateNew($datas)
    {
        $ret = array();
        foreach ($datas as $data) {
            $ret[] = $this->createNew($data);
        }

        $this->getEntityManager()->flush();

        return $ret;
    }

    public function createNew($data, $flush = true)
    {
        $em = $this->getEntityManager();

        $title = $data['title'];

        $type = null;
        if (isset($data['type'])) {
            $type = $data['type'];
        }

        if ($tag = $this->findOneBy(array(
                'title' => $title,
                'type' => $type
            ))) {
            return $tag;
        }

        $class = $this->getClassMetadata()->getName();
        $tag = new $class();
        $tag->setTitle($title);
        $tag->setType($type);

        $em->persist($tag);

        if ($flush) {
            $em->flush();
        }

        return $tag;
    }

    public function findByIds($ids)
    {
        return $this->findByIdsQB($ids)
            ->getQuery()
                ->getResult();
    }

    protected function findByIdsQB($ids)
    {
        $qb = $this->getBaseQB();
        $qb->andWhere($qb->expr()->in('t.id', $ids));

        return $qb;
    }

    public function findByTitles($titles, $type = null)
    {
        $qb = $this->findByTitlesQB($titles);
        if ($type) {
            $this->byType($qb, $type);
        }

        return $qb->getQuery()
                  ->getResult();
    }

    protected function findByTitlesQB($titles)
    {
        $qb = $this->getBaseQB();
        $qb->andWhere($qb->expr()->in('t.title', $titles));

        return $qb;
    }

    public function getBaseQB()
    {
        $qb = $this->createQueryBuilder('t')
            ->add('orderBy', 't.title')
            ->andWhere('t.deletedAt IS NULL')
            ;

        return $qb;
    }

    public function getSitemapList($categories = null)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('slug', 'slug')
            ->addScalarResult('updated_at', 'updated_at', 'datetime');

        $sql = "SELECT  t.`slug`, t.`updated_at`
                FROM `" . $this->getClassMetadata()->table['name'] . "` t
                LEFT JOIN `page_tag` pt
                    ON t.id = pt.tag_id
                LEFT JOIN `page` p
                    ON pt.page_id = p.id
                WHERE p.`published_at` IS NOT NULL
                    AND p.`status`='" . Page::STATUS_PUBLISHED . "'
                    AND p.`page_type`='" . Page::TYPE_ARTICLE . "'";
        if (!empty($categories)) {
            $sql .= ' AND p.`category_id` IN (' . implode(',', $categories) . ')';
        }
        $sql .= ' GROUP BY t.id';

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        return $query;
    }

    public function replaceInJoinTables($correct, $incorrect)
    {
        $connection = $this->getEntityManager()->getConnection();
        $tables = $this->getRelationTablesNames('Wf\Bundle\CmsBaseBundle\Entity\Tag');

        foreach ($tables as $table) {
            // replace incorrect tag id with correct tag
            // ignore duplicates: if not replaced, correct tag is already associated with page

            if (!is_null($correct)) {
                $sql = "UPDATE IGNORE $table t
                    SET t.tag_id = :correct
                    WHERE t.tag_id = :incorrect";
                $connection->executeUpdate($sql, array('correct' => $correct, 'incorrect' => $incorrect));
            }

            // delete form relation tables
            $sql = "DELETE FROM $table WHERE tag_id = ?";
            $connection->executeUpdate($sql, array($incorrect));
        }
    }

    public function replaceInJoinColumns($correct, $incorrect)
    {
        $em = $this->getEntityManager();
        $entityName = 'Wf\Bundle\CmsBaseBundle\Entity\Tag';
        $associations = $this->getRelations($entityName);

        foreach ($associations as $association) {
            // joinColumn relations
            if (!isset($association['joinTable'])) {
                $fieldName = $association['fieldName'];

                if ($association['isOwningSide']) {
                    $table = $association['sourceEntity'];
                } else {
                    $table = $association['targetEntity'];
                }

                // replace incorrect tag id with correct tag
                $query = $em->createQuery("UPDATE $table t
                    SET t.$fieldName = :correct
                    WHERE t.$fieldName = :incorrect");
                $query->execute(array('correct' => $correct, 'incorrect' => $incorrect));
            }
        }
    }

    protected function getRelations($entity)
    {
        $em = $this->getEntityManager();
        $mf = $em->getMetadataFactory();
        $am = $mf->getAllMetaData();

        $return = array();
        foreach ($am as $k=>$v) {
            $associations = $v->getAssociationMappings();
            foreach ($associations as $association) {
                $targetEntity = $association['targetEntity'];
                if (is_subclass_of($targetEntity, $entity)) {
                    $return[] = $association;

                }
            }
        }

        return $return;
    }

    protected function getRelationTablesNames($entity)
    {
        $associations = $this->getRelations($entity);
        $return = array();

        foreach ($associations as $association) {
            if (isset($association['joinTable'])) {
                $return[] = $association['joinTable']['name'];
            }
        }

        return array_unique($return);
    }


}