<?php

namespace Wf\Bundle\CmsBaseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use JMS\Serializer\Annotation as JMSS;
use Wf\Bundle\CmsBaseBundle\Entity\Traits\TaggableTrait;

/**
 * @ORM\MappedSuperclass
 * @Assert\Callback(methods={"imageExists"})
 */
abstract class Image
{
    use TaggableTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("integer")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="Tag", cascade={"persist"})
     * @ORM\JoinTable(name="image_tag",
     *      joinColumns={@ORM\JoinColumn(name="image_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id")}
     *      )
     */
    private $tags;

    /**
     * @JMSS\Groups({"list", "edit", "show"})
     * @JMSS\Accessor(getter="getUploadPrefix")
     * @JMSS\Type("string")
     * @var string
     */
    private $uploadPrefix;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", name="created_at")
     * @JMSS\Groups({"show", "list", "import"})
     */
    private $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", name="updated_at")
     * @JMSS\Groups({"show", "list", "import"})
     */
    private $updatedAt;

    /**
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(name="slug", type="string", length=128, unique=true)
     * @JMSS\Groups({"list", "show", "import", "version"})
     * @JMSS\Type("string")
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="form.error.article.title.notblank")
     * @JMSS\Groups({"list", "show", "import"})
     */
    private $title = null;

    /**
     * @ORM\Column(type="string", length=16000, nullable=true)
     * @JMSS\Groups({"show", "import", "list"})
     */
    private $description = null;

    /**
     * @ORM\Column(type="string", name="image_name", length=255, nullable=true)
     * @JMSS\Groups({"show", "list", "import", "version"})
     * @JMSS\Type("string")
     */
    protected $imageName = null;

    /**
     * @ORM\Column(name="source_id", type="string", length=255, unique=true, nullable=true)
     * @JMSS\Groups({"show"})
     */
    private $sourceId;

    /**
     * @Assert\Image()
     * @Vich\UploadableField(mapping="image", fileNameProperty="imageName")
     * @JMSS\Exclude
     */
    protected $image;

    /**
     *
     * @ORM\Column(name="fields", type="json_array", nullable=true)
     * @JMSS\Groups({"import"})
     * @JMSS\Type("array")
     */
    protected $fields = array();
    
    /**
     * @JMSS\Groups({"show", "list"})
     * @JMSS\Type("string")
     */
    protected $contentType = 'image';

    public function __construct()
    {
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set imageName
     *
     * @param string $imageName
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;
    }

    /**
     * Get imageName
     *
     * @return string
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }

    public function getSourceId()
    {
        return $this->sourceId;
    }

    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;
    }

    public function getFields() {
        return $this->fields;
    }

    public function setFields($fields) {
        $this->fields = $fields;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function imageExists($context)
    {
        if (empty($this->imageName) && empty($this->image)) {
            $context->addViolationAt(
                'video',
                'This value cannot be blank!',
                array(),
                null
            );
        }
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
}