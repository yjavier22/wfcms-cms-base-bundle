<?php

namespace Wf\Bundle\CmsBaseBundle\Publish\Listener;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Wf\Bundle\CmsBaseBundle\Publish\Manager\BaseManager;

/**
 * @author cv
 */
class PublishListener
{
    /**
     * @var BaseManager
     */
    protected $manager;
    
    protected $toUpdate = array();
    protected $toDelete = array();
    protected $pageClass;

    public function __construct(BaseManager $manager, $pageClass)
    {
        $this->manager = $manager;
        $this->pageClass = $pageClass;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->trackEntity($args, 'persist');
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->trackEntity($args, 'update');
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $this->trackEntity($args, 'remove');
    }

    protected function trackEntity(LifecycleEventArgs $args, $type)
    {
        $page = $args->getEntity();
        if (!$page instanceof $this->pageClass) {
            return;
        }

        $changeset = array(
            'publishedAt' => array($page->getPublishedAt(), $page->getPublishedAt()),
            'status' => array($page->getStatus(), $page->getStatus()),
        );
        if ($args instanceof PreUpdateEventArgs) {
            $changeset = $args->getEntityChangeSet();
        }
        if (!$page->isPublished()) {
            $this->toDelete[] = $page;
        } else {
            if ($page->isPublishedNow()) {
                if ($type == 'remove') {//published page was removed
                    $this->toDelete[] = $page;
                } else {
                    $this->toUpdate[] = array($type, $page);
                }
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        $toUpdate = array_merge($this->toUpdate);
        $toDelete = array_merge($this->toDelete);
        $this->toUpdate = array();
        $this->toDelete = array();

        if (!empty($toDelete)) {
            $this->manager->removePages($toDelete);
        }

        foreach ($toUpdate as $trackInfo) {
            list($type, $page) = $trackInfo;
            $this->manager->processPage($page, $type);
        }
    }

}
