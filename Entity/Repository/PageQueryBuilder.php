<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Wf\Bundle\CmsBaseBundle\Publish\Manager\BaseManager;

class PageQueryBuilder extends QueryBuilder
{
    public $rootAlias = 'it';
    protected $list;
    protected $ids;
    protected $excluded;

    /**
     * @var ClassMetadata
     */
    protected $classMetadata;

    protected $entityName;

    public function __construct(EntityManager $em, ClassMetadata $classMetadata)
    {
        parent::__construct($em);

        $this->classMetadata = $classMetadata;
        $this->entityName = $this->classMetadata->name;

        $this->select($this->rootAlias)
            ->from($this->entityName, $this->rootAlias)
            ->orderBy($this->rootAlias . '.publishedAt', 'DESC')
            ->addOrderBy($this->rootAlias . '.createdAt', 'DESC')
            ;
    }

    /** Query methods */

    public function onlyActive($onlyActive)
    {
        if ($onlyActive) {
            $orderBys = [];
            foreach ($this->getDQLPart('orderBy') as $orderByPart) {
                if (strpos($orderByPart->getParts()[0], 'it.createdAt') === false) {
                    $orderBys[] = $orderByPart;
                }
            }
            $this->resetDQLPart('orderBy', $orderBys);

            $this->andWhere('it.status = :status')
                ->setParameter('status', Page::STATUS_PUBLISHED)
                ->andWhere('it.publishedAt <= CURRENT_TIMESTAMP()')
                ;
        } else {
            //@TODO remove $onlyActive from wheres (if it was bound before)
        }

        return $this;
    }

    public function byType($pageType)
    {
        $this
            ->andWhere($this->rootAlias . ' INSTANCE OF ' . $this->getClassByPageType($pageType))
            ;

        return $this;
    }

    public function bySlug($pageSlug)
    {
        $this
            ->andWhere($this->rootAlias . '.slug = :slug')
            ->setParameter('slug', $pageSlug)
        ;

        return $this;
    }

    public function byCategory($category, $categoryChildren = null, $categoryFields = true)
    {
        if (is_null($category)) {
            return $this;
        }
        $categoryIds = array($category->getId());
        if (!is_null($categoryChildren)) {
            foreach ($categoryChildren as $categoryChild) {
                $categoryIds[] = $categoryChild->getId();
            }
        }

        if ($categoryFields) {
            $this->addSelect('c.title, c.slug');
        }

        $this
            ->leftJoin($this->rootAlias . '.categories', 'c')
            ->andWhere($this->expr()->in('c.id', $categoryIds))
            ;

        return $this;
    }

    public function byEdition($edition)
    {

        $this
            ->andWhere('it.edition = :edition')
            ->setParameter('edition', $edition->getId())
            ->orderBy('it.position','ASC')
            ;

        return $this;
    }

    public function byAuthor($author)
    {
        $this->innerJoin('it.authors', 'a', 'WITH', 'a.id = :author')
            ->setParameter('author', $author->getId());

        return $this;
    }

    public function byStatus($status)
    {
        $this
            ->andWhere($this->rootAlias . '.status = :status')
                ->setParameter('status', $status)
            ;

        return $this;
    }

    public function byIds($ids)
    {
        $this->_byIds($ids);
        $this->andWhere($this->expr()->in($this->rootAlias . '.id', ':ids'))
             ->setParameter('ids', $this->ids);

        return $this;
    }

    public function groupById()
    {
        $this->addGroupBy($this->rootAlias . '.id');
        return $this;
    }

    public function publishedAtStartingFrom(\DateTime $startTime = null)
    {
        if (is_null($startTime)) {
            $startTime = new \DateTime('1900-01-01');
        }

        $this->andWhere('it.publishedAt >= :startTime')
                ->setParameter('startTime', $startTime);

        return $this;
    }

    public function publishedAtUntil(\DateTime $endTime = null)
    {
        if (isset($endTime)) {
            $this->andWhere('it.publishedAt < :endTime')
                ->setParameter('endTime', $endTime);
        }

        return $this;
    }

    public function hasMedia($type)
    {
        return $this->andWhere(sprintf('%s.has%ss = true',
            $this->rootAlias,
            ucfirst($type)
        ));
    }

    public function hasMainImage()
    {
        return $this->andWhere(sprintf('%s.mainImage IS NOT NULL', $this->rootAlias));
    }


    public function excludeIds($pageIds)
    {
        $this->_excludeIds($pageIds);
        if (!empty($pageIds)) {
            $this->andWhere($this->expr()->notIn(sprintf('%s.id', $this->rootAlias), ':excluded'))
                 ->setParameter('excluded', $this->excluded);
        }

        return $this;
    }

    public function byTemplate($templateName)
    {
        $this->andWhere('it.template = :template')
            ->setParameter('template', $templateName);

        return $this;
    }

    public function byList($listName = null)
    {
        $this->list = $listName;

        return $this;
    }
    
    public function noOrder()
    {
        $this->resetDQLPart('orderBy');
    }

    /** Getter methods */

    protected function getClassByPageType($pageType)
    {
        if (isset($this->classMetadata->discriminatorMap[$pageType])) {
            return $this->classMetadata->discriminatorMap[$pageType];
        }
    }

    public function getIds()
    {
        return $this->ids;
    }

    public function getExcluded()
    {
        return $this->excluded;
    }

    public function getList()
    {
        return $this->list;
    }

    /** result methods */

    public function getResults()
    {
        return $this->getQuery()->execute();
    }

    public function getSingleResult()
    {
        return $this->getQuery()->getOneOrNullResult();
    }

    public $redisUsed = false;
    
    public function getQuery()
    {
        $ret = parent::getQuery();
        if (0 && !$this->redisUsed) {
            error_log('[NOLISTQUERY] ' . $ret->getSQL());
            ob_start();
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $trace = ob_get_clean();
            error_log('[NOLISTTRACE] ' . $trace);
        }

        return $ret;
    }

    public function limit($maxResults = null, $firstResult = 0)
    {
        if (is_null($maxResults)) {
            return $this;
        }

        $this
            ->setMaxResults($maxResults)
            ->setFirstResult($firstResult)
            ;

        return $this;
    }

    /** Helper methods */

    protected function _byIds($ids)
    {
        if (empty($ids)) {
            return;
        }
        if (empty($this->ids)) {
            $this->ids = (array)$ids;
        } else {
            $this->ids = array_merge($this->ids, (array)$ids);
        }
    }

    protected function _excludeIds($ids)
    {
        if (empty($ids)) {
            return;
        }

        if (empty($this->excluded)) {
            $this->excluded = (array)$ids;
        } else {
            $this->excluded = array_merge($this->excluded, (array)$ids);
        }
    }

    public function excludeNonNews()
    {
        $this->leftJoin($this->rootAlias . '.categories', 'c')
            ->andWhere($this->expr()->isNull('c.type'));

        return $this;
    }

    public function byDateEdition(\DateTime $date)
    {
        $this->andWhere('it.dateEdition = :date')
            ->setParameter('date', $date->format('Y-m-d'));

        return $this;
    }

}