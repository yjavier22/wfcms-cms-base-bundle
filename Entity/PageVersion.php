<?php

namespace Wf\Bundle\CmsBaseBundle\Entity;

use JMS\Serializer\SerializerInterface;
use Gedmo\Timestampable\Traits\setCreatedAt;
use Wf\Bundle\CmsBaseBundle\Entity\Collection\PageEditorModuleCollectionFactory;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMSS;
use Wf\Bundle\CmsBaseBundle\Manager\PageManager;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class PageVersion
{
    const SERIALIZE_FORMAT = 'xml';
    const START_VERSION = 1;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMSS\Groups({"list", "edit"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Page")
     * @ORM\JoinColumn(name="page_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * @JMSS\Exclude
     */
    private $page;

    /**
     * @var SerializerInterface
     * @JMSS\Exclude
     */
    protected $serializer;

    /**
     * @var boolean
     * @JMSS\Exclude
     */
    protected $frozen = false;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", name="created_at")
     * @JMSS\Groups({"list", "edit"})
     */
    private $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", name="updated_at")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime", name="published_at", nullable=true)
     * @JMSS\Groups({"edit"})
     */
    private $publishedAt;

    /**
     * @ORM\Column(type="integer", name="version_no", nullable=true)
     * @JMSS\Groups({"edit", "list"})
     */
    private $versionNo = 0;

    /**
     * @ORM\Column(type="text", name="page_class")
     * @JMSS\Exclude
     */
    protected $pageClass;

    /**
     * @ORM\Column(type="text", length=65535, name="page_data")
     * @JMSS\Exclude
     * Holds the serialized page in this version
     */
    protected $pageData;

    /**
     * @JMSS\Exclude
     */
    private $pageDataObject;

    /**
     * @JMSS\Exclude
     * @var \Wf\Bundle\CmsBaseBundle\Manager\PageManager
     */
    private $pageManager;

    /**
     * @JMSS\Groups({"edit"})
     * @JMSS\Accessor(getter="getAuthor")
     */
    private $author;
    
    /**
     * @JMSS\Groups({"edit"})
     * @JMSS\Accessor(getter="getCreator")
     */
    private $creator;

    /**
     * @JMSS\Groups({"edit"})
     * @JMSS\Accessor(getter="getPublisher")
     */
    private $publisher;

    /**
     * @JMSS\Groups({"edit"})
     * @JMSS\Accessor(getter="getStatus")
     */
    private $status;

    public function __construct()
    {
        $this->setVersionNo(self::START_VERSION);
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setPage($page)
    {
        $this->page = $page;

        if (is_null($this->pageData)) {
            $this->setPageData($page);
        }
    }

    public function getPage()
    {
        return $this->page;
    }

    public function __clone()
    {
        $now = new \DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->publishedAt = null;
        $page = $this->getPageData();
        if ($page) {
            $page->setInProgress();
            $page->setCreatedAt($now);
            $page->setUpdatedAt($now);
            $page->setPublisher(null);
            $this->setPageData($page);
        }
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * inject page manager in order to setup a page object when the page is created and published at the same time
     * @param \Wf\Bundle\CmsBaseBundle\Manager\PageManager $pageManager
     */
    public function setPageManager(PageManager $pageManager)
    {
        $this->pageManager = $pageManager;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        $page = $this->getPageData();
        if ($page) {
            $page->setCreatedAt($createdAt);
        }
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        $page = $this->getPageData();
        if ($page) {
            $page->setUpdatedAt($updatedAt);
        }
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setVersionNo($versionNo)
    {
        $this->versionNo = $versionNo;
    }

    public function getVersionNo()
    {
        return $this->versionNo;
    }

    public function setPageData($pageData)
    {
        $publishedAt = null;
        if (!is_string($pageData)) {
            $publishedAt = $pageData->getPublishedAt();
            $this->pageClass = get_class($pageData);
            $this->serializer->setGroups(array('version'));
            $pageData = $this->serializer->serialize($pageData, self::SERIALIZE_FORMAT);
            $this->pageDataObject = null;
        }

        $this->pageData = $pageData;

        if (!empty($publishedAt)) {
            $this->_setPublishedAt($publishedAt);
        }
    }

    public function getPageData()
    {
        if (empty($this->pageData)) {
            return null;
        }

        if (is_null($this->pageDataObject)) {
            $this->pageDataObject = $this->serializer->deserialize($this->pageData, $this->pageClass, self::SERIALIZE_FORMAT);
            $this->pageManager->setupPageEntity($this->pageDataObject);
            $this->pageDataObject->setVersion($this);
        }
        if ($this->pageDataObject instanceof $this->pageClass && !$this->pageDataObject->getId() && $this->getPage()) {
            $this->pageDataObject->setId($this->getPage()->getId());
        }

        return $this->pageDataObject;
    }

    public function getRawPageData()
    {
        return $this->pageData;
    }

    public function setPublishedAt($publishedAt)
    {
        $this->_setPublishedAt($publishedAt);
        $page = $this->getPageData();
        if ($page) {
            $page->setPublishedAt($publishedAt);
        }
    }

    protected function _setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;
    }

    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    public function setFrozen($frozen)
    {
        $this->frozen = $frozen;
    }

    public function getFrozen()
    {
        return $this->frozen;
    }

    public function freeze()
    {
        $this->setFrozen(true);
    }

    public function unpublish()
    {
        $this->publishedAt = null;
        $page = $this->getPageData();
        $page->unpublish();
        $this->setPageData($page);
    }

    public function getAuthor()
    {
        $author = $this->getPageData()->getAuthor();
        return $author ? $author->getName() : '';
    }
    
    public function getCreator()
    {
        $creator = $this->getPageData()->getCreator();
        return $creator ? $creator->getName() : '';
    }

    public function getPublisher()
    {
        $publisher = $this->getPageData()->getPublisher();
        return $publisher ? $publisher->getName() : '';
    }

    public function getStatus()
    {
        return $this->getPageData()->getStatus();
    }

    public function getModules()
    {
        return $this->getPageData()->getModules();
    }
}
