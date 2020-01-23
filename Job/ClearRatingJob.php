<?php

namespace Wf\Bundle\CmsBaseBundle\Job;

use BCC\ResqueBundle\ContainerAwareJob as BaseJob;
use Wf\Bundle\CmsBaseBundle\Entity\Page;

/**
 * a php-resque that clears varnish cache
 */
class ClearRatingJob extends BaseJob
{
    /**
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Page $page
     * @return \Wf\Bundle\CmsBaseBundle\Job\ClearRatingJob
     */
    static public function create($pageId)
    {
        $job = new self();
        $job->args = array(
            'pageId' => $pageId,
        );

        return $job;
    }

    public function run($args)
    {
        $container = $this->getContainer();
        $pageRepository = $container->get('wf_cms.repository.page');
        $page = $pageRepository->find($args['pageId']);

        if ($page) {
            $container->get('wf_cms.frontend_cache.manager')->purgeRatingsCache($page);
            error_log('Cleared rating varnish cache for ' . $args['pageId']);
        }
    }
}