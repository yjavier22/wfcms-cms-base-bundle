<?php

namespace Wf\Bundle\CmsBaseBundle\Pagerfanta\Adapter;

use Pagerfanta\Adapter\AdapterInterface;

/**
 *
 * @author ciprian
 */
interface MaxedAdapter extends AdapterInterface
{
    public function setMaxResults($maxResults = null);
}

