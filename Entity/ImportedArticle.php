<?php

namespace Wf\Bundle\CmsBaseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use JMS\Serializer\Annotation as JMSS;

/**
 * @ORM\MappedSuperclass
 */
abstract class ImportedArticle
{
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
    private $updateAt;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="form.error.article.title.notblank")
     * @JMSS\Groups({"list", "edit"})
     */
    private $title = null;

    /**
     * @ORM\Column(type="json_array")
     */
    private $text;

    /**
     * @ORM\Column(type="json_array")
     */
    private $images;

    /**
     * @ORM\Column(type="json_array")
     */
    private $galleries;

    /**
     * @ORM\Column(name="source_id", type="string", length=255, unique=true)
     * @JMSS\Groups({"list", "show"})
     */
    private $sourceId;

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        return $this->createdAt = $createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->updatedAt = $updatedAt;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        return $this->title = $title;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        return $this->text = $text;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function setImages($images)
    {
        return $this->images = $images;
    }

    public function getGalleries()
    {
        return $this->galleries;
    }

    public function setGalleries($galleries)
    {
        return $this->galleries = $galleries;
    }

    public function getSourceId()
    {
        return $this->sourceId;
    }

    public function setSourceId($sourceId)
    {
        return $this->sourceId = $sourceId;
    }

    public function __toString() {
        return $this->getSourceId();
    }
}