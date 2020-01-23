<?php

namespace Wf\Bundle\CmsBaseBundle\Job;

use BCC\ResqueBundle\ContainerAwareJob as BaseJob;
use Wf\Bundle\CmsBaseBundle\Entity\Page;

/**
 * a php-resque worker that publishes a page
 *
 * @author cv
 */
class PublishJob extends BaseJob
{
    /**
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Page $page
     * @return \Wf\Bundle\CmsBaseBundle\Job\Publish
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
        $resque = $container->get('bcc_resque.resque');
        \Locale::setDefault($container->getParameter('locale'));
        $pageRepository = $container->get('wf_cms.repository.page');
        
        $pageId = $args['pageId'];
        /* @var $page \Wf\Bundle\CmsBaseBundle\Entity\Page */
        $page  = $pageRepository->find($pageId);
        if (!$page) {
            return;
        }
        
        if ($page->getNextPublishedAt()) {
            $nextPublishJob = FuturePublishJob::create($page);
            $resque->enqueueAt($page->getNextPublishedAt(), $nextPublishJob);
        }
        
        $cacheJob = new ClearCacheJob();
        $cacheJob->args = array('pageId' => $pageId);
        $resque->enqueue($cacheJob);
    }
}