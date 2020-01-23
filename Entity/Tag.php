<?php

namespace Wf\Bundle\CmsBaseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMSS;

/**
 * @ORM\MappedSuperclass
 * @JMSS\ExclusionPolicy("all")
 */
abstract class Tag
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Expose
     * @JMSS\Type("integer")
     */
    private $id;

    /**
     * @ORM\Column(name="slug", type="string", length=128, unique=true)
     * @Gedmo\Slug(fields={"title"})
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("string")
     * @JMSS\Expose
     */
    private $slug;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @Assert\NotBlank(message="form.error.tag.title.notblank")
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("string")
     * @JMSS\Expose
     */
    private $title;

    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @var datetime $updatedAt
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     * @JMSS\SerializedName("deletedAt")
     */
    protected $deletedAt;

    /**
     * @ORM\Column(name="type", type="string", length=50, nullable=true)
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("string")
     * @JMSS\Expose
     */
    private $type;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    public function __toString()
    {
        return $this->getTitle();
    }

    public function getHrefName()
    {
        $str = strtolower($this->__toString());
        $ret = preg_replace('@[^a-z0-9]@', '_', $str);

        return $ret;
    }

    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }


    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function equals(Tag $tag)
    {
        $tagId = $tag->getId();
        if (!empty($tagId)) {
            return $this->getId() == $tagId;
        } else {
            return $this->getTitle() == $tag->getTitle()
                && $this->getType() == $tag->getType();
        }
    }
}