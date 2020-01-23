<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Wf\Bundle\CmsBaseBundle\Entity\Repository\TreeRepository;
use Doctrine\ORM\Query\QueryBuilder;
use Wf\Bundle\CmsBaseBundle\Sitemap\SitemapCapableInterface;

/**
 * CategoryRepository
 */
class CategoryRepository extends TreeRepository implements SitemapCapableInterface
{
    public function getMainMenu()
    {
        return $this->getMenu('main');
    }

    public function reorderMainMenu($order)
    {
        return $this->reorderMenu('main', $order);
    }

    protected function reorderMenu($menuName, $order)
    {
        $categories = $this->getMenu($menuName);
        foreach ($order as $position=>$categoryId) {
            foreach ($categories as $category) {
                if ($category->getId() == $categoryId) {
                    $category->getMenu($menuName)->setPosition($position);
                    $this->_em->persist($category);
                }
            }
        }

        $this->_em->flush();

        return true;
    }

    protected function getMenu($menuName)
    {
        return $this->getMenuQB($menuName)->getQuery()->getResult();
    }

    protected function getMenuQB($menuName)
    {
        $qb = $this->getBaseTreeQB();
        $alias = $qb->getRootAlias();

        $qb
            ->leftJoin($alias . '.mainMenuPosition', 'm')
            ->andWhere('m.menuName = :menuName')
                ->setParameter('menuName', $menuName)
            ->orderBy('m.position')
            ;
        return $qb;
    }

    public function getBaseTreeQB($onlyActive = true, $allTypes = false) {
        $qb = $this->getRootNodesQueryBuilder();
        $alias = $qb->getRootAlias();
        $qb->andWhere($alias . '.active = :active')
                ->setParameter('active', $onlyActive)
        ;
        if (!$allTypes) {
           $qb->andWhere($alias . '.type IS NULL');
        }

        return $qb;
    }

    public function getBaseTree()
    {
        return $this->getBaseTreeQB(true)
                    ->getQuery()
                    ->execute();
    }

    public function getSelectChoices($callback = null)
    {
        $qb = $this->getBaseTreeQB(true);

        $q = $qb->getQuery();
        $r = $q->getResult();

        return $this->walkTree($r, $callback);
    }

    public function walkTree($nodes = null, $callback = null, $onlyActive = true)
    {
        if (is_null($nodes)) {
            $nodes = $this->getBaseTree($onlyActive);
        }

        $result = array();
        foreach($nodes as $node) {
            if ($onlyActive && !$node->isActive()) {
                continue;
            }

            $result[$node->getId()] = isset($callback) ? call_user_func_array($callback, array($node)) : $node;
            $children = $node->getChildren();
            if ($children->count()) {
                $result = $result + $this->walkTree($children, $callback, $onlyActive);
            }
        }

        return $result;
    }

    public function getAllCategories($rootCategorySlug = null, $excludeCategories = null)
    {
        $categoryTree = $this->getBaseTree();
        $categories = array();
        if (!is_null($rootCategorySlug)) {
            foreach($categoryTree as $baseCategory) {
                if ($baseCategory->getParent()) {
                    continue;
                }
                if ($baseCategory->getSlug() == $rootCategorySlug) {
                    $categories[] = $baseCategory;
                    break;
                }
            }
        } else {
            foreach($categoryTree as $baseCategory) {
                if ($baseCategory->getParent()) {
                    continue;
                }
                if (!in_array($baseCategory->getSlug(), $excludeCategories)) {
                    $categories[] = $baseCategory;
                }
            }
        }

        $categories = $this->walkTree($categories, function($category) {
            return $category->getId();
        });

        return $categories;
    }

    /**
     * @return QueryBuilder $qb
     */
    public function getBaseQB($onlyActive = true, $allTypes = false)
    {
        $qb = $this->createQueryBuilder('c')
            ->add('orderBy', 'c.createdAt DESC')
            ;

        if ($onlyActive) {
            $qb
                ->andWhere('c.active = :active')
                    ->setParameter('active', true)
                ;
        }
        if (!$allTypes) {
            $qb->andWhere('c.type IS NULL');
        }

        return $qb;
    }

    public function getSitemapList($categories = null)
    {
        if (isset($categories)) {
            $qb = $this->getBaseQB();
            $qb->andWhere($qb->expr()->in('c.id', $categories));

            return $qb;
        }
        return $this->getBaseQB(true);
    }

    public function getRootCategories($onlyActive = true, $allTypes = false)
    {
        $qb = $this->getBaseQB($onlyActive, $allTypes);
        $alias = $qb->getRootAlias();

        $categories = $qb->andWhere($alias . '.level = 0')
            ->getQuery()
            ->getResult();

        return $this->indexResultsBySlug($categories);
    }

    protected function indexResultsBySlug($results)
    {
        $ret = array();

        foreach ($results as $result) {
            $ret[$result->getSlug()] = $result;
        }

        return $ret;
    }

    public function getBlogsCategory()
    {
        return $this->findOneBySlug('blogs');
    }
    
    public function activeChildren($onlyActive = true, $allTypes = false, $node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        $qb = $this->childrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode);
        if ($onlyActive) {
            $qb->andWhere($qb->getRootAlias() . '.active=:active')
                ->setParameter('active', true);
        }
        if (!$allTypes) {
            $qb->andWhere($qb->getRootAlias() . '.type IS NOT NULL');
        }
        return $qb->getQuery()->getResult();
    }

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
}