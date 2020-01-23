<?php

namespace Wf\Bundle\CmsBaseBundle\Job;

use BCC\ResqueBundle\ContainerAwareJob as BaseJob;

/**
 * enqueues a category cache clear
 *
 * @author cv
 */
class ClearCategoriesCacheJob extends BaseJob
{
    /**
     * @param array $categories
     * @return \Wf\Bundle\CmsBaseBundle\Job\ClearCategoriesCacheJob
     */
    static public function create($categories)
    {
        $ids = array();
        foreach($categories as $category) {
            if (!is_object($category)) {
                var_dump($categories);exit;
            }
            $ids[] = $category->getId();
        }
        
        $job = new self();
        $job->args = array(
            'ids' => $ids,
        );
        
        return $job;
    }
    
    public function run($args)
    {
        $cacheManager = $this->getContainer()->get('wf_cms.frontend_cache.manager');
        $cacheManager->purgeCategoriesCache($args['ids']);
    }
}