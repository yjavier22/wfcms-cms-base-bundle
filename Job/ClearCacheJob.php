<?php

namespace Wf\Bundle\CmsBaseBundle\Job;

use BCC\ResqueBundle\ContainerAwareJob as BaseJob;
use Wf\Bundle\CmsBaseBundle\Entity\Page;

/**
 * a php-resque that clears varnish cache
 *
 * @author cv
 */
class ClearCacheJob extends BaseJob
{
    /**
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Page $page
     * @return \Wf\Bundle\CmsBaseBundle\Job\ClearCacheJob
     */
    static public function create(Page $page)
    {
        $job = new self();
        $job->args = array(
            'pageId' => $page->getId(),
        );
        
        return $job;
    }
    
    public function run($args)
    {
        $container = $this->getContainer();
        $pageRepository = $container->get('wf_cms.repository.page');
        $page = $pageRepository->find($args['pageId']);
        if ($page) {
            $container->get('wf_cms.frontend_cache.manager')->purgePageCache($page);
//            error_log('Cleared varnish cache for ' . $args['pageId']);
        }
        
        
        $resque = $container->get('bcc_resque.resque');
        \Locale::setDefault($container->getParameter('locale'));
        $categories = $page->getAllCategories();
        if (!empty($categories)) {
            $categoryClearJob = ClearCategoriesCacheJob::create($categories);
            $resque->enqueue($categoryClearJob);
        }
    }
}