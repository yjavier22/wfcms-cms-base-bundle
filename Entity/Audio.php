<?php

namespace Wf\Bundle\CmsBaseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Wf\Bundle\CmsBaseBundle\Entity\Traits\TaggableTrait;
use Wf\Bundle\CommonBundle\Validator as WfAssert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use JMS\Serializer\Annotation as JMSS;

/**
 * @ORM\MappedSuperclass
 * @Assert\Callback(methods={"audioExists"})
 */
abstract class Audio
{
    use TaggableTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMSS\Groups({"list", "edit"})
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="Tag", cascade={"persist"})
     * @ORM\JoinTable(name="audio_tag",
     *      joinColumns={@ORM\JoinColumn(name="audio_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id")}
     *      )
     */
    private $tags;

    /**
     * @JMSS\Groups({"list", "edit", "show"})
     * @JMSS\Accessor(getter="getUploadPrefix")
     * @var string
     */
    private $uploadPrefix;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", name="created_at")
     * @JMSS\Groups({"list", "show"})
     */
    private $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", name="updated_at")
     * @JMSS\Groups({"list", "show"})
     */
    private $updatedAt;

    /**
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(name="slug", type="string", length=128, unique=true)
     * @JMSS\Groups({"list", "show"})
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="form.error.article.title.notblank")
     * @JMSS\Groups({"list", "show"})
     */
    private $title = null;

    /**
     * @ORM\Column(type="string", length=16000, nullable=true)
     * @JMSS\Groups({"show"})
     */
    private $description = null;

    /**
     * @ORM\Column(type="string", name="audio_name", length=255, nullable=true)
     * @JMSS\Groups({"show", "list"})
     */
    protected $audioName = null;

    /**
     * @ORM\Column(type="integer", name="duration", nullable=true)
     * @JMSS\Groups({"show", "list"})
     */
    protected $duration = null;

    /**
     * @WfAssert\M4aAudio()
     * @Vich\UploadableField(mapping="audio", fileNameProperty="audioName")
     * @JMSS\Exclude
     */
    protected $audio;
    
    /**
     * @JMSS\Groups({"show", "list"})
     * @JMSS\Type("string")
     */
    protected $contentType = 'audio';

    public function __construct()
    {
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getUploadPrefix()
    {
        $createdAt = $this->getCreatedAt();
        if (!$createdAt) {
            $this->setCreatedAt(new \DateTime());
            $createdAt = $this->getCreatedAt();
        }

        return $createdAt->format('Y/m/d');
    }
    /**
     * Set createdAt
     *
     * @param datetime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get createdAt
     *
     * @return datetime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param datetime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get updatedAt
     *
     * @return datetime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * get slug
     *
     * @return string
     */
    public function getSlug() {
        return $this->slug;
    }

    /**
     * set slug
     *
     * @param string $slug
     */
    public function setSlug($slug) {
        $this->slug = $slug;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set audioName
     *
     * @param string $audioName
     */
    public function setAudioName($audioName)
    {
        $this->audioName = $audioName;
    }

    /**
     * Get audioName
     *
     * @return string
     */
    public function getAudioName()
    {
        return $this->audioName;
    }

    /**
     * get duration in seconds
     *
     * @return integer
     */
    public function getDuration() {
        return $this->duration;
    }

    /**
     * sets duration in seconds
     *
     * @param integer $duration
     */
    public function setDuration($duration) {
        $this->duration = $duration;
    }

    public function getAudio()
    {
        return $this->audio;
    }

    public function setAudio($audio)
    {
        if (is_null($audio)) {
            return;
        }

        $this->audio = $audio;
    }

    public function audioExists($context)
    {
        if (empty($this->audioName) && empty($this->audio)) {
            $context->addViolationAt(
                'video',
                'This value cannot be blank!',
                array(),
                null
            );
        }
    }
}