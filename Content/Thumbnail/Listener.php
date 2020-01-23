<?php

namespace Wf\Bundle\CmsBaseBundle\Content\Thumbnail;

use Sonata\NotificationBundle\Consumer\ConsumerInterface;
use Sonata\NotificationBundle\Consumer\ConsumerEvent;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Sonata\NotificationBundle\Backend\MessageManagerBackend as NotificationQueue;

class Listener implements ConsumerInterface
{
    const LOG_PREFIX = '[ArticleListener]';

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var Wf\Bundle\CmsBaseBundle\Content\Thumbnail\Generator
     */
    protected $generator;

    /**
     * @var Symfony\Bridge\Monolog\Logger
     */
    protected $logger;

    /**
     * @var Sonata\NotificationBundle\Backend\MessageManagerBackend
     */
    protected $notificationQueue;

    /**
     * name of queue that will receive the thumbnail generated message
     *
     * @var string
     */
    protected $thumbnailGeneratedType;

    /**
     * @var boolean
     */
    protected $debug;

    public function __construct(
            EntityManager $em,
            Generator $generator,
            NotificationQueue $notificationQueue,
            Logger $logger,
            $thumbnailGeneratedType,
            $debug)
    {
        $this->em = $em;
        $this->generator = $generator;
        $this->notificationQueue = $notificationQueue;
        $this->logger = $logger;
        $this->thumbnailGeneratedType = $thumbnailGeneratedType;
        $this->debug = $debug;
    }

    protected function createFilteredThumbnails($entity, $filters, $url) {
        foreach($filters as $filter) {
            if ($this->debug) {
                echo '[' . __CLASS__ . '::' . __METHOD__ . ']Creating thumb: ' . $this->generator->thumbnailRelativePath($entity, $filter);
            }
            $this->logger->debug(self::LOG_PREFIX . 'Creating thumbs: ' . $this->generator->thumbnailRelativePath($entity, $filter));
            $this->generator->thumbnail($entity, $filter, $url, true);
        }
    }

    public function process(ConsumerEvent $event)
    {
        $message = $event->getMessage();

        $entityClass = $message->getValue('entity');
        $entityId = $message->getValue('entity_id');
        $url = $message->getValue('url');
        $filter = $message->getValue('filter', null);
        if ($this->debug) {//log to console
            $data = array(
                'entity' => $entityClass,
                'entity_id' => $entityId,
                'url' => $url,
                'filter' => $filter ? (array)$filter : '',
            );
            if ($this->debug) {
                echo '[' . __CLASS__ . '::' . __METHOD__ . '] Recieved generate thumbnail message ' . json_encode($data) . PHP_EOL;
            }
        }

        if (($entity = $this->em->find($entityClass, $entityId))) {
            $this->generator->clearThumbsCache($entity, $filter);

            if (($ret = (bool) $this->generator->thumbnail($entity, '', $url, true))) {
                if (is_array($filter)) {
                    $this->createFilteredThumbnails($entity, $filter, $url);
                }
                //Send thumbnail generated message
                $message = array(
                    'entity' => $entityClass,
                    'entity_id' => $entityId,
                    'thumb_path' => $this->generator->thumbnailRelativePath($entity),
                );

                $this->notificationQueue->createAndPublish($this->thumbnailGeneratedType, $message);
                $this->notificationQueue->saveAllAndFlush();
                $this->generator->clearThumbsCache($entity, $filter);

                $this->logger->debug(self::LOG_PREFIX . 'Thumbnail generated ' . $message['thumb_path']);

                return true;
            } else {
                $this->logger->err(self::LOG_PREFIX . 'Thumbnail failed');
                return false;
            }
        } else {
            $this->logger->warn(self::LOG_PREFIX . sprintf('Could not find entity %s[id=%s]', $entityClass, $entityId));
        }

        return true;
    }

}
