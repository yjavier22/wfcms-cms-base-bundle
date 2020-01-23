<?php

namespace Wf\Bundle\CmsBaseBundle\Pagerfanta\Adapter;

use Pagerfanta\Adapter\AdapterInterface;
use Wf\Bundle\CmsBaseBundle\Entity\Repository\PageQueryBuilder;
use Wf\Bundle\CmsBaseBundle\Publish\Manager\BaseManager;

/**
 * Pagerfanta adapter that supports maximum results and items promotion
 *
 * @author cv
 */
class PageDoctrineORMAdapter implements AdapterInterface, MaxedAdapter, OffsetAdapter
{
    /**
     *
     * @var \Wf\Bundle\CmsBaseBundle\Publish\Manager\BaseManager
     */
    protected $publishListManager;
    protected $maxResults = PHP_INT_MAX;
    protected $offset;

    /**
     * @var \Wf\Bundle\CmsBaseBundle\Entity\Repository\PageQueryBuilder
     */
    protected $qb;

    public function __construct(PageQueryBuilder $query, BaseManager $publishListManager, $maxResults)
    {
        $this->qb = $query;
        $this->publishListManager = $publishListManager;
        $this->setMaxResults($maxResults);
    }

    public function getItems($offset, $length)
    {
        $listName = $this->qb->getList();
        $listItems = $this->publishListManager->getListSlice($listName, $offset, $length, $this->qb->getExcluded());

        return $listItems;
    }

    public function getNbResults()
    {
        $listName = $this->qb->getList();
        $excluded = $this->qb->getExcluded();

        return min($this->maxResults, $this->publishListManager->getListSize($listName, $excluded)) - (isset($this->offset) ? $this->offset : 0);
    }

    protected function adjustInterval(&$offset, &$length)
    {
        $results = $this->getNbResults();
        $excludedIds = $this->qb->getExcluded();
        $excluded = is_array($excludedIds) ? count($excludedIds) : 0;
        $setOffset = isset($this->offset) ? $this->offset : 0;
        if ($offset + $length - $excluded > $results + $setOffset) {
            $length = $results + $setOffset - $offset + $excluded;
        }

        if (isset($this->offset)) {
            $offset += $this->offset;
        }
    }
    
    public function getSlice($offset, $length)
    {
        $this->adjustInterval($offset, $length);
        // if no $lenght is given, it'll return all IDs in Redis, MySQL sends a *lot* of data
        if ($length > 75 || empty($length)) {
            if (1 && empty($this->list)) {
                ob_start();
                debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                $trace = ob_get_clean();
                error_log('[NOLIMITQUERY] ' . $trace);
            }

            $length = 75;
        }
        
        $listItems = array_values($this->getItems($offset, $length));
        $ids = array_slice($listItems, 0, $length);
        if (empty($ids)) {
            return new \ArrayIterator(array());
        }

        $this->qb->resetDQLPart('orderBy');
//        $this->qb->resetDQLPart('join');
        $this->qb->setParameters(array());
        $this->qb->select($this->qb->rootAlias);
        $this->qb->where($this->qb->expr()->in($this->qb->rootAlias . '.id', ':ids'))
                 ->setParameter('ids', $ids);
        $this->qb->redisUsed = true;
        $this->qb->limit($length);

        $results = $this->qb->getQuery()->getResult();
        if (empty($results)) {
            return new \ArrayIterator(array());
        }

        //order results by the order established in the list
        $ret = array();
        foreach($ids as $id) {
            foreach ($results as $page) {
                if ($page->getId() == $id) {
                    $ret[] = $page;
                    break;
                }
            }
        }

        return new \ArrayIterator($ret);
    }

    public function setMaxResults($maxResults = null)
    {
        $this->maxResults = !empty($maxResults) ? $maxResults : PHP_INT_MAX;
    }
    
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }
}