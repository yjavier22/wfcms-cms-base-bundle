<?php

namespace Wf\Bundle\CmsBaseBundle\Pagerfanta\Adapter;

use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Adapter\DoctrineORMAdapter as BaseDoctrineORMAdapter;
use Wf\Bundle\CmsBaseBundle\Entity\Repository\PageQueryBuilder;

/**
 * Pagerfanta adapter that supports maximum results and items promotion
 *
 * @author cv
 */
class DoctrineORMAdapter extends BaseDoctrineORMAdapter implements MaxedAdapter, PromotedAdapter, OffsetAdapter
{

    /**
     * @param \Pagerfanta\Adapter\DoctrineORMAdapter $adapter
     * @return \Wf\Bundle\CmsBaseBundle\Pagerfanta\Adapter\DoctrineORMAdapter
     */
    static public function createFromAdapter(AdapterInterface $adapter)
    {
        return new self($adapter->getQuery(), $adapter->getFetchJoinCollection());
    }
    protected $promoted = array();
    protected $promotedOffset = 0;
    protected $promotedLength = 0;
    protected $maxResults = PHP_INT_MAX;
    protected $offset = 0;

    /**
     * Constructor.
     *
     * @param \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder $query A Doctrine ORM query or query builder.
     * @param Boolean $fetchJoinCollection Whether the query joins a collection (true by default).
     */
    public function __construct($query, $fetchJoinCollection = true)
    {
        parent::__construct($query, $fetchJoinCollection);
    }

    public function setPromotedItems($items)
    {
        $this->promoted = array();
        foreach ($items as $item) {
            $this->promoted[] = $item;
        }
    }

    public function getMaxResults()
    {
        return $this->maxResults;
    }

    public function setMaxResults($maxResults = null)
    {
        $this->maxResults = !empty($maxResults) ? $maxResults : PHP_INT_MAX;
    }

    public function getPromotedSlice()
    {
        return array($this->promotedOffset, $this->promotedLength);
    }

    public function getSlice($offset, $length)
    {
        $offset = $offset + $this->offset;
        $advertisedNo = count($this->promoted);
        if ($offset < $advertisedNo) {
            $items = array();
            $this->isSliceAdvertised = true;
            if ($offset + $length < $advertisedNo) {
                $this->promotedOffset = $offset;
                $this->promotedLength = $length;
                $offset = 0;
                $length = 0;
                $items = array_slice($this->promoted, $this->promotedOffset, $this->promotedLength);
            } else {
                $this->promotedOffset = $offset;
                $this->promotedLength = $advertisedNo - $offset;
                $length = $offset + $length - $advertisedNo;
                $offset = 0;
                $parentItems = parent::getSlice($offset, $length);
                $items = array_slice($this->promoted, $this->promotedOffset, $this->promotedLength);
                foreach ($parentItems as $item) {
                    $items[] = $item;
                }
            }

            return new \ArrayIterator($items);
        } else {
            $offset = $offset - $advertisedNo;
        }

        return parent::getSlice($offset, $length);
    }

    public function getNbResults()
    {
        $allResults = count($this->promoted) + parent::getNbResults() - $this->offset;
        return min($this->maxResults, $allResults);
    }

    public function setOffset($offset)
    {
        $this->offset($offset);
    }
}