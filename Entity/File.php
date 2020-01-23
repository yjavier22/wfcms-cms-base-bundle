<?php

namespace Wf\Bundle\CmsBaseBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File as FSFile;
use Symfony\Component\Validator\Constraints as Assert;
use Wf\Bundle\CmsBaseBundle\Entity\Traits\TaggableTrait;
use Wf\Bundle\CommonBundle\Validator as WfAssert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use JMS\Serializer\Annotation as JMSS;

/**
 * @ORM\MappedSuperclass
 */
class File
{
    use TaggableTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMSS\Groups({"list", "edit", "show", "version"})
     */
    protected $id;

    /**
     * @ORM\ManyToMany(targetEntity="Tag", cascade={"persist"})
     * @ORM\JoinTable(name="file_tag",
     *      joinColumns={@ORM\JoinColumn(name="file_id", referencedColumnName="id")},
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

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", name="created_at")
     * @JMSS\Groups({"list", "show"})
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", name="updated_at")
     * @JMSS\Groups({"list", "show"})
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(name="slug", type="string", length=128, unique=true)
     * @JMSS\Groups({"list", "show"})
     * @var string
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="form.error.file.title.notblank")
     * @JMSS\Groups({"list", "show"})
     * @var string
     */
    private $title = null;

    /**
     * @ORM\Column(type="string", length=16000, nullable=true)
     * @JMSS\Groups({"show"})
     * @var string
     */
    private $description = null;

    /**
     * @ORM\Column(type="string", name="filename", length=255, nullable=true)
     * @JMSS\Groups({"show", "list"})
     * @var string
     */
    protected $fileName;

    /**
     * @ORM\Column(type="integer", name="filesize")
     * @JMSS\Groups({"show", "list"})
     * @var integer
     */
    protected $size = 0;

    /**
     * @ORM\Column(type="string", name="mime_type")
     * @JMSS\Groups({"show", "list"})
     * @var string
     */
    protected $mimeType;

    /**
     * @Assert\NotBlank()
     * @Vich\UploadableField(mapping="generic_file", fileNameProperty="fileName")
     * @JMSS\Exclude
     * @var \Symfony\Component\HttpFoundation\File\File
     */
    protected $file;
    
    /**
     * @JMSS\Groups({"show", "list"})
     * @JMSS\Type("string")
     */
    protected $contentType = 'file';

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

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function setSize($size)
    {
        $this->size = $size;
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }

    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile(FSFile $file)
    {
        $this->file = $file;
        $this->setSize($file->getSize());
        $this->setMimeType($file->getMimeType());
        if (method_exists($file, 'getClientOriginalName')) {
            $this->setFileName($file->getClientOriginalName());
        }
    }

    public function getRelatedData() {
        return array(
            'id' => $this->getId(),
            'type' => 'file',
            'mime_type' => $this->getMimeType(),
            'title' => $this->getTitle(),
            'file_name' => $this->getFileName(),
            'upload_prefix' => $this->getUploadPrefix(),
        );
    }

}