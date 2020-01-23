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
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * @ORM\MappedSuperclass
 * @Assert\Callback(methods={"isVideoValid", "thumbExists"})
 */
abstract class Video
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
     * @ORM\JoinTable(name="video_tag",
     *      joinColumns={@ORM\JoinColumn(name="video_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id")}
     *      )
     */
    private $tags;

    /**
     * @WfAssert\Video(mimeTypes={"video/x-flv", "video/mp4"} )
     * @Vich\UploadableField(mapping="video", fileNameProperty="videoName")
     * @JMSS\Exclude
     */
    protected $video;

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
    protected $slug;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="form.error.article.title.notblank")
     * @JMSS\Groups({"list", "show"})
     */
    protected $title = null;

    /**
     * @ORM\Column(type="string", length=16000, nullable=true)
     * @JMSS\Groups({"show", "list"})
     */
    private $description = null;

    /**
     * @ORM\Column(type="string", name="video_name", length=255, nullable=true)
     * @JMSS\Groups({"show", "list"})
     */
    protected $videoName = null;

    /**
     *
     * @ORM\Column(type="string", name="thumb_name", length=255, nullable=true)
     * @JMSS\Groups({"show", "list"})
     */
    protected $thumbName = null;


    /**
     * @Assert\Image()
     * @Vich\UploadableField(mapping="thumb", fileNameProperty="thumbName")
     * @JMSS\Exclude
     */
    protected $thumb;

    /**
     *
     * @ORM\Column(type="string", name="source", length=255, nullable=true)
     * @JMSS\Groups({"show", "list"})
     */
    protected $source = null;

    /**
     *
     * @ORM\Column(type="string", name="media_id", length=255, nullable=true)
     * @JMSS\Groups({"show", "list"})
     */
    protected $mediaId = null;
    
    /**
     * @JMSS\Groups({"show", "list"})
     * @JMSS\Type("string")
     */
    protected $contentType = 'video';

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
     * Set videoName
     *
     * @param string $videoName
     */
    public function setVideoName($videoName)
    {
        $this->videoName = $videoName;
    }

    /**
     * set thumbName
     *
     * @param string $thumbName
     */
    public function setThumbName($thumbName)
    {
        $this->thumbName = $thumbName;
    }

    /**
     * Get videoName
     *
     * @return string
     */
    public function getVideoName()
    {
        return $this->videoName;
    }

    public function getThumbName()
    {
        return $this->thumbName;
    }

    public function getVideo()
    {
        return $this->video;
    }

    public function setVideo($video)
    {
        if (is_null($video)) {
            return;
        }

        $this->video = $video;
    }


    public function getThumb()
    {
        return $this->thumb;
    }

    public function setThumb($thumb)
    {
        if (is_null($thumb)) {
            return null;
        }

        $this->thumb = $thumb;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setMediaId($mediaId)
    {
        $this->mediaId = $mediaId;
    }

    public function getMediaId()
    {
        return $this->mediaId;
    }


    public function isVideoValid($context)
    {
        if (empty($this->source) && empty($this->video) && empty($this->videoName)) {
            $context->addViolationAt(
                'video',
                'This value cannot be blank!',
                array(),
                null
            );
        }
    }

    public function thumbExists($context)
    {
        if (empty($this->thumbName) && empty($this->thumb)) {
            $context->addViolationAt(
                'thumb',
                'This value cannot be blank!',
                array(),
                null
            );
        }
    }
}