<?php

namespace Wf\Bundle\CmsBaseBundle\Pagerfanta\Adapter;

use Wf\Bundle\CmsBaseBundle\Manager\PageManager;

/**
 * Redis Tracker adapter for Pagerfanta
 *
 * @author cv
 */
class RedisTrackerAdapter implements MaxedAdapter
{
    /**
     * @var \Wf\Bundle\CmsBaseBundle\Manager\PageManager
     */
    protected $manager;

    /**
     * @var string
     */
    protected $trackingNamespace;
    protected $maxResults = PHP_INT_MAX;

    /**
     * @param string $trackingNamespace - redis namespace to query
     * @param \Wf\Bundle\CmsBaseBundle\Manager\PageManager $manager - redis repository
     */
    public function __construct($trackingNamespace, PageManager $manager = null)
    {
        $this->trackingNamespace = $trackingNamespace;
        if (isset($manager)) {
            $this->setManager($manager);
        }
    }

    /**
     * @return \Wf\Bundle\CmsBaseBundle\Manager\PageManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param \Wf\Bundle\CmsBaseBundle\Manager\PageManager $manager
     */
    public function setManager(PageManager $manager)
    {
        $this->manager = $manager;
    }

    public function getMaxResults()
    {
        return $this->maxResults;
    }

    public function setMaxResults($maxResults = null)
    {
        $this->maxResults = !empty($maxResults) ? $maxResults : PHP_INT_MAX;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        return min($this->getMaxResults(), $this->getManager()->getTrackingPagesCount($this->trackingNamespace));
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        $slice = $this->getManager()->getTrackingPages($this->trackingNamespace, $offset, $offset + $length);
        if (is_null($slice)) {
            return array();
        }

        return $slice;
    }
}