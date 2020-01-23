<?php

namespace Wf\Bundle\CmsBaseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMSS;

/**
 * @ORM\MappedSuperclass
 * @JMSS\ExclusionPolicy("all")
 */
abstract class AdvertisementBanner
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMSS\Groups({"list", "edit"})
     */
    private $id;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @Gedmo\Translatable
     * @JMSS\Expose
     * @JMSS\Type("string")
     * @JMSS\Groups({"edit", "list"})
     */
    private $title;

    /**
     * @var string $width
     *
     * @ORM\Column(name="width", type="smallint", length=255)
     * @JMSS\Expose
     * @JMSS\Type("string")
     * @JMSS\Groups({"edit", "list"})
     */
    private $width;

    /**
     * @var string $height
     *
     * @ORM\Column(name="height", type="smallint", length=255)
     * @JMSS\Expose
     * @JMSS\Type("string")
     * @JMSS\Groups({"edit", "list"})
     */
    private $height;

    /**
     * @var string $tag
     *
     * @ORM\Column(name="tag", type="string", length=255)
     * @Gedmo\Translatable
     * @JMSS\Expose
     * @JMSS\Type("string")
     * @JMSS\Groups({"edit", "list"})
     */
    private $tag;

    /**
     * @var boolean $active
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active = true;


    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function setActive($active)
    {
        $this->active = $active;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function setHeight($height)
    {
        $this->height = $height;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function setWidth($width)
    {
        $this->width = $width;
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    public function __toString()
    {
        if ($this->title) {
            return $this->title;
        }

        return '';
    }

}