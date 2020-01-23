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
abstract class AdvertisementPage
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
     * @var string $type
     *
     * @ORM\Column(name="type", type="string", length=255)
     * @JMSS\Expose
     * @JMSS\Type("string")
     * @JMSS\Groups({"edit", "list"})
     */
    private $type;

    /**
     * @var string $details
     *
     * @ORM\Column(name="details", type="string", length=255)
     * @Gedmo\Translatable
     * @JMSS\Expose
     * @JMSS\Type("string")
     * @JMSS\Groups({"edit", "list"})
     */
    private $details;

    /**
     * @var string $OASPage
     *
     * @ORM\Column(name="OAS_page", type="string", length=255)
     * @JMSS\Expose
     * @JMSS\Type("string")
     * @JMSS\Groups({"edit", "list"})
     */
    private $OASPage;

    /**
     * @var string $OASPositions
     *
     * @ORM\Column(name="OAS_positions", type="string", length=255)
     * @JMSS\Expose
     * @JMSS\Type("string")
     * @JMSS\Groups({"edit", "list"})
     */
    private $OASPositions;

    /**
     * @var string $locked
     *
     * @ORM\Column(name="locked", type="boolean", length=255)
     * @JMSS\Expose
     * @JMSS\Type("boolean")
     * @JMSS\Groups({"edit", "list"})
     */
    private $locked = false;

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

    public function getOASPage()
    {
        return $this->OASPage;
    }

    public function setOASPage($OASPage)
    {
        $this->OASPage = $OASPage;
    }

    public function getOASPositions()
    {
        return $this->OASPositions;
    }

    public function setOASPositions($OASPositions)
    {
        $this->OASPositions = $OASPositions;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getDetails()
    {
        return $this->details;
    }

    public function setDetails($details)
    {
        $this->details = $details;
    }

    public function getLocked()
    {
        return $this->locked;
    }

    public function setLocked($locked)
    {
        $this->locked = $locked;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function setActive($active)
    {
        $this->active = $active;
    }

    public function __toString()
    {
        if ($this->title) {
            return $this->title;
        }

        return '';
    }
}