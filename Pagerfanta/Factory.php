<?php

namespace Wf\Bundle\CmsBaseBundle\Pagerfanta;

use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\ArrayAdapter;
use Wf\Bundle\CmsBaseBundle\Entity\Repository\PageQueryBuilder;
use Wf\Bundle\CmsBaseBundle\Publish\Manager\BaseManager;
use Wf\Bundle\CmsBaseBundle\Pagerfanta\Adapter\DoctrineORMAdapter;
use Wf\Bundle\CmsBaseBundle\Pagerfanta\Adapter\NativeQueryAdapter;
use Wf\Bundle\CmsBaseBundle\Pagerfanta\Adapter\PageDoctrineORMAdapter;
use Doctrine\ORM\NativeQuery;
use FOS\ElasticaBundle\Paginator\TransformedPaginatorAdapter;
use FOS\ElasticaBundle\Paginator\FantaPaginatorAdapter;

/**
 * @author cv
 */
class Factory
{
    /**
     * @var \Wf\Bundle\CmsBaseBundle\Publish\Manager\BaseManager
     */
    protected $publishListManager;

    protected $maxResults;

    protected $perPage;

    public function __construct(BaseManager $publishListManager, $perPage)
    {
        $this->publishListManager = $publishListManager;
        $this->maxResults = $publishListManager->getListMaxSize();
        $this->perPage = $perPage;
    }

    public function createPager($query, $page = 1, $pagerAdapterClass = null, $perPage = null)
    {
        if ($perPage) {
            $this->perPage = $perPage;
        }

        if ($query instanceof Pagerfanta) {
            return $this->setupPager($query, $page);
        }
        if ($query instanceof AdapterInterface) {
            return $this->createPagerFromAdapter($query, $page);
        }
        
        if (is_null($pagerAdapterClass)) {
            if ($query instanceof PageQueryBuilder && $query->getList()) {
                $adapter = new PageDoctrineORMAdapter($query, $this->publishListManager, $this->maxResults);
            } elseif ($query instanceof NativeQuery) {
                $adapter = new NativeQueryAdapter($query);
            } elseif (is_array($query)) {
                $adapter = new ArrayAdapter($query);
            } elseif ($query instanceof TransformedPaginatorAdapter) {
                $adapter = new FantaPaginatorAdapter($query);
            } else {
                $adapter = new DoctrineORMAdapter($query);
            }
        } else {
            $adapter = new $pagerAdapterClass($query, $this->publishListManager, $this->maxResults);
        }

        return $this->createPagerFromAdapter($adapter, $page);
    }

    public function createPagerFromAdapter(AdapterInterface $adapter, $page = 1)
    {
        $pager = new Pagerfanta($adapter);

        return $this->setupPager($pager, $page);
    }

    protected function setupPager(Pagerfanta $pager, $page = 1)
    {
        $pager->setMaxPerPage($this->perPage);
        if ($page) {
            $pager->setCurrentPage($page);
        }

        return $pager;
    }
}