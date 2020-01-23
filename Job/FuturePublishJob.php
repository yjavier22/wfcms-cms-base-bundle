<?php

namespace Wf\Bundle\CmsBaseBundle\Job;

use BCC\ResqueBundle\ContainerAwareJob as BaseJob;
use Wf\Bundle\CmsBaseBundle\Entity\Page;

/**
 * @author cv
 */
class FuturePublishJob extends BaseJob
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
        \Locale::setDefault($container->getParameter('locale'));
        $pageManager = $container->get('wf_cms.page_manager');
        $pageRepository = $container->get('wf_cms.repository.page');
        
        $pageId = $args['pageId'];
        /* @var $page \Wf\Bundle\CmsBaseBundle\Entity\Page */
        $page  = $pageRepository->find($pageId);
        if (!$page) {
            return;
        }
        
        $pageManager->isPublished($page);
    }
}