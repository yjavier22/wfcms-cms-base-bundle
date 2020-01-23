<?php

namespace Wf\Bundle\CmsBaseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use JMS\Serializer\Annotation as JMSS;
use Wf\Bundle\CommonBundle\Util\ClassUtil;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * @ORM\MappedSuperclass
 * @JMSS\ExclusionPolicy("all")
 * @Assert\Callback(methods={"isTypeValid"})
 */
abstract class Category
{
    const TYPE_NEWS = null;
    const TYPE_STATIC = 'static';
    const TYPE_BLOG = 'blog';
    const TYPE_MULTIMEDIA = 'multimedia';

    const TYPE_DEFAULT = self::TYPE_NEWS;

    static public function getTypes()
    {
        return ClassUtil::getConstants(get_class(), 'TYPE_');
    }

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     * @JMSS\Expose
     * @JMSS\Type("integer")
     * @JMSS\Groups({"edit", "list", "version"})
     */
    private $id;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer")
     */
    private $lft;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer")
     */
    private $rgt;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(type="integer", nullable=true)
     */
    private $root;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    private $level;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $parent;


    /**
     * @ORM\Column(name="slug", type="string", length=128, unique=true)
     * @Gedmo\Slug(handlers={
     *      @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\RelativeSlugHandler", options={
     *          @Gedmo\SlugHandlerOption(name="relationField", value="parent"),
     *          @Gedmo\SlugHandlerOption(name="relationSlugField", value="slug"),
     *          @Gedmo\SlugHandlerOption(name="separator", value="/")
     *      })
     * }, fields={"title"}, updatable=false)
     * @JMSS\Expose
     * @JMSS\Type("string")
     * @JMSS\Groups({"edit", "list", "version"})
     */
    protected $slug;

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
     * @Gedmo\Translatable
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @var boolean $active
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active = true;

    /**
     * @ORM\Column(name="type", type="string", length=16, nullable=true)
     */
    private $type;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pictureName = null;

    /**
     * @Assert\Image()
     * @Vich\UploadableField(mapping="category_picture", fileNameProperty="pictureName")
     */
    private $picture;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $template = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, name="article_template")
     */
    private $articleTemplate = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getLeft()
    {
        return $this->lft;
    }

    public function setLeft($lft)
    {
        $this->lft = $lft;
    }

    public function getRight()
    {
        return $this->rgt;
    }

    public function setRight($rgt)
    {
        $this->rgt = $rgt;
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

        return $this;
    }

    /**
     * Set lft
     *
     * @param integer $lft
     */
    public function setLft($lft)
    {
        $this->lft = $lft;
    }

    /**
     * Get lft
     *
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set root
     *
     * @param integer $root
     */
    public function setRoot($root)
    {
        $this->root = $root;
    }

    /**
     * Get root
     *
     * @return integer
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set level
     *
     * @param integer $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    /**
     * Get level
     *
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Add children
     *
     * @param Category $children
     */
    public function addCategory(Category $children)
    {
        $this->children[] = $children;
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

    /**
     * Set active
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    public function isActive()
    {
        return $this->getActive();
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function __toString()
    {
        return $this->getTitle();
    }

    public function setPicture($picture)
    {
        $this->picture = $picture;
    }

    public function getPicture()
    {
        return $this->picture;
    }

    public function setPictureName($pictureName)
    {
        $this->pictureName = $pictureName;
    }

    public function getPictureName()
    {
        return $this->pictureName;
    }

    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getParents()
    {
        $parents = array();
        $category = $this;
        while($category->getParent()) {
            $parents[] = $category->getParent();
            $category = $category->getParent();
        }
        
        return $parents;
    }

    public function getTitlePath() 
    {
        $parent = $this->getParent();

        if (!empty($parent)) {
            return $parent->getTitlePath() . '/' . $this->getTitle();
        }

        return $this->getTitle();
    }

    public function getArticleTemplate()
    {
        return $this->articleTemplate;
    }

    public function setArticleTemplate($articleTemplate)
    {
        $this->articleTemplate = $articleTemplate;
    }

    public  function getType()
    {
        return $this->type;
    }

    public  function setType($type)
    {
        $this->type = $type;
    }

    public function isTypeValid(ExecutionContextInterface $context)
    {
        if (!in_array($context->getValue()->getType(), static::getTypes())) {
            $context->addViolationAt('type', 'form.error.category.type.notachoice', array());
            return false;
        }

        return true;
    }
}
