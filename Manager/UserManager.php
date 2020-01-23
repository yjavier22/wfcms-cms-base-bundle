<?php

namespace Wf\Bundle\CmsBaseBundle\Manager;

use FOS\UserBundle\Doctrine\UserManager as BaseUserManager;
use Wf\Bundle\CmsBaseBundle\Sitemap\SitemapCapableInterface;

/**
 * manages users
 *
 * @author cv
 */
class UserManager extends BaseUserManager implements SitemapCapableInterface
{
    /**
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    public function getSitemapList()
    {
        return $this->findUsers();
    }

}
