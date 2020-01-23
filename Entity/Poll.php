<?php

namespace Wf\Bundle\CmsBaseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMSS;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\MappedSuperclass
 */
class   Poll
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMSS\Groups({"edit", "list"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Category")
     * @JMSS\Exclude
     */
    private $category;

    /**
     * @Assert\File(
     *     maxSize="1M",
     *     mimeTypes={"image/png", "image/jpeg", "image/pjpeg"}
     * )
     * @Vich\UploadableField(mapping="poll_picture", fileNameProperty="imageName")
     *
     * @var File $image
     */
    private $image;

    /**
     * @ORM\Column(type="string", length=255, name="image_name", nullable=true)
     *
     * @var string $imageName
     */
    private $imageName;

    /**
     * @ORM\Column(name="question", type="string")
     * @JMSS\Groups({"edit", "list"})
     */
    private $question;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", name="created_at")
     * @JMSS\Groups({"edit", "list"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", name="start_date")
     * @JMSS\Groups({"edit", "list"})
     */
    private $startDate;

    /**
     * @ORM\Column(type="datetime", name="end_date")
     * @JMSS\Groups({"edit", "list"})
     */
    private $endDate;

    /**
     * @JMSS\Groups({"edit", "list"})
     * @JMSS\Accessor(getter="getUploadPrefix")
     */
    protected $uploadPrefix;
    
    /**
     * @JMSS\Groups({"show", "list"})
     * @JMSS\Type("string")
     */
    protected $contentType = 'poll';

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Add options
     *
     * @param PollOption $options
     * @return Poll
     */
    public function addOption(PollOption $option)
    {
        $this->options[] = $option;

        return $this;
    }

    public function addOptions(PollOption $option)
    {
        $this->addOption($option);
    }

    /**
     * Remove options
     *
     * @param PollOption $options
     */
    public function removeOption($option)
    {
        $this->options->removeElement($option);
    }

    
    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions($options)
    {
        $this->options = $options;
    }


    /**
     * Set section
     *
     * @param Category $section
     * @return Poll
     */
    public function setCategory(Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get section
     *
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    public function setImageName($imageName)
    {
        $this->imageName = $imageName;
        return $this;
    }

    public function getImageName()
    {
        return $this->imageName;
    }

    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }
    public function setQuestion($question)
    {
        $this->question = $question;
        return $this;
    }

    public function getQuestion()
    {
        return $this->question;
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
     * Set startDate
     *
     * @param datetime $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * Get startDate
     *
     * @return datetime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param datetime $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * Get endDate
     *
     * @return datetime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    public function __toString()
    {
        if ($this->question) {
            return $this->question;
        }

        return '';
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
