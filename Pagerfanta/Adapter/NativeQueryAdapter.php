<?php

namespace Wf\Bundle\CmsBaseBundle\Pagerfanta\Adapter;

use Doctrine\ORM\NativeQuery;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * page a native query
 */
class NativeQueryAdapter implements AdapterInterface
{
    /**
     * @var \Doctrine\ORM\NativeQuery
     */
    private $query;


    /**
     * @param \Doctrine\ORM\NativeQuery $query
     */
    public function __construct(NativeQuery $query)
    {
        $this->query = $query;
    }

    /**
     * Returns the query
     *
     * @return \Doctrine\ORM\NativeQuery
     */
    public function getQuery()
    {
        return $this->query;
    }

    public function getNbResults()
    {
        $sql = preg_replace('/^SELECT\b.+\bFROM/is', 'SELECT COUNT(*) as total FROM', $this->getQuery()->getSQL());

        $params = $this->getQuery()->getParameters();
        if (is_object($params) && method_exists($params, 'toArray')) {
            $params = $params->toArray();
        }
        $count = $this->getQuery()->getEntityManager()->getConnection()->fetchColumn($sql, $params);
        return $count;
    }

    public function getSlice($offset, $length)
    {
        $query = clone $this->getQuery();
        $query->setParameters( $this->getQuery()->getParameters() );

        $sql   = $query->getSql();
        $sql   .= sprintf(' LIMIT %d, %d', $offset, $length);
        $query->setSql($sql);

        return $query->getResult();
    }
}