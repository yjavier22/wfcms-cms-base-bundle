<?php

namespace Wf\Bundle\CmsBaseBundle\Entity;

use Wf\Bundle\CmsBaseBundle\Entity\Collection\PageEditorModuleCollection;
use Wf\Bundle\CmsBaseBundle\Entity\Collection\PageEditorModuleCollectionFactory;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMSS;
use Wf\Bundle\CommonBundle\Util\SlugUtil;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class Page
{
    const STOP_WORDS = 'nbsp|lt|gt|amp|quot|039';

    /**
     * @JMSS\Exclude
     */
    protected $bundleName = null; //used to get the template path - MUST be overwritten by the site's bundle

    const STATUS_DEFAULT = 'default';
    const STATUS_PROGRESS = 'default';
    const STATUS_VERIFIED = 'verified';
    const STATUS_READY = 'ready';
    const STATUS_PUBLISHED = 'published';

    const TYPE_HOMEPAGE = 'homepage';
    const TYPE_BOARD = 'board';
    const TYPE_SIDEBAR = 'sidebar';
    const TYPE_CATEGORY = 'category';
    const TYPE_ARTICLE = 'article';
    const TYPE_TAG = 'tag';
    const TYPE_ADS = 'ads';
    const TYPE_LISTING = 'page_listing';
    const TYPE_LISTING_TEMPLATE = 'page_listing_template';
    const TYPE_METATAGS = 'metatags';
    const TYPE_AUTHOR = 'author';

    const FORM_TYPE = 'page';

    const MEDIA_TYPE_IMAGE = 'image';
    const MEDIA_TYPE_AUDIO = 'audio';
    const MEDIA_TYPE_VIDEO = 'video';
    const MEDIA_TYPE_FILE = 'file';

    const SETTING_COMMENTS_MODERATED = 'moderated';
    const SETTING_COMMENTS_ALLOWED = 'allowed';
    const SETTING_COMMENTS_NOTALLOWED = 'not_allowed';
    const SETTING_COMMENTS_DEFAULT = self::SETTING_COMMENTS_ALLOWED;

    const SETTING_VOTABLE_YES = 'yes';
    const SETTING_VOTABLE_NO = 'no';
    const SETTING_VOTABLE_DEFAULT = self::SETTING_VOTABLE_YES;

    const RENDERER_DEFAULT = 'html';
    const RENDERER_METATAGS = 'metatags';
    const RENDERER_LISTING = 'listing';

    static public function getStatuses()
    {
        return array(
            self::STATUS_PROGRESS,
            self::STATUS_VERIFIED,
            self::STATUS_PUBLISHED
        );
    }

    static public function getCommentOptions()
    {
        return array(
            self::SETTING_COMMENTS_ALLOWED,
            // self::SETTING_COMMENTS_MODERATED, // planning to use either FB or Disqus comments, this setting doesn't make too much sense
            self::SETTING_COMMENTS_NOTALLOWED,
        );
    }

    static public function getVotableOptions()
    {
        return array(
            self::SETTING_VOTABLE_NO,
            self::SETTING_VOTABLE_YES,
        );
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("Wf\Bundle\CmsBaseBundle\Entity\Category")
     */
    protected $category;

    /**
     * @ORM\ManyToOne(targetEntity="PageMetadata")
     * @ORM\JoinColumn(name="metadata_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("Wf\Bundle\CmsBaseBundle\Entity\PageMetadata")
     */
    protected $metadata;

    /**
     * @ORM\Column(name="slug", type="string", length=255, unique=true)
     * @Gedmo\Slug(handlers={
     *      @Gedmo\SlugHandler(class="Wf\Bundle\CmsBaseBundle\Utils\CategoryRelativeSlugHandler", options={
     *          @Gedmo\SlugHandlerOption(name="relationField", value="category"),
     *          @Gedmo\SlugHandlerOption(name="relationSlugField", value="slug"),
     *          @Gedmo\SlugHandlerOption(name="separator", value="/"),
     *          @Gedmo\SlugHandlerOption(name="urilize", value=false)
     *      })
     * }, fields={"title"}, updatable=true)
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("string")
     */
    protected $slug;

    /**
     * @ORM\ManyToMany(targetEntity="User")
     * @ORM\JoinTable(name="page_author",
     *      joinColumns={@ORM\JoinColumn(name="page_id", onDelete="CASCADE", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="author_id", referencedColumnName="id")}
     * )
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("array<Wf\Bundle\CmsBaseBundle\Entity\User>")
     */
    protected $authors;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("Wf\Bundle\CmsBaseBundle\Entity\User")
     */
    protected $creator;

    /**
     * @ORM\ManyToMany(targetEntity="Category", cascade={"persist"})
     * @ORM\JoinTable(name="page_category",
     *      joinColumns={@ORM\JoinColumn(name="page_id", onDelete="CASCADE", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="category_id", referencedColumnName="id")}
     * )
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("array<Wf\Bundle\CmsBaseBundle\Entity\Category>")
     */
    protected $categories;

    /**
     * @ORM\ManyToMany(targetEntity="Tag", cascade={"persist"})
     * @ORM\JoinTable(name="page_tag",
     *      joinColumns={@ORM\JoinColumn(name="page_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id")}
     * )
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("array<Wf\Bundle\CmsBaseBundle\Entity\Tag>")
     */
    protected $tags;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="publisher_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("Wf\Bundle\CmsBaseBundle\Entity\User")
     */
    protected $publisher;

    /**
     * @ORM\OneToOne(targetEntity="PageVersion")
     * @ORM\JoinColumn(name="version_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * @JMSS\Exclude
     */
    protected $currentVersion;

    /**
     * @ORM\OneToOne(targetEntity="PageVersion")
     * @ORM\JoinColumn(name="next_version_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * @JMSS\Exclude
     */
    protected $nextVersion;

    /**
     * @JMSS\Groups({"edit", "list"})
     * @JMSS\Accessor(getter="getPageType")
     * @JMSS\Type("string")
     */
    protected $type;

    /**
     * @JMSS\Groups({"edit"})
     * @JMSS\Type("Wf\Bundle\CmsBaseBundle\Entity\PageVersion")
     */
    protected $version;

    /**
     * @JMSS\Groups({"edit"})
     * @JMSS\Type("string")
     * @JMSS\Accessor(getter="getPageSlug", setter="setPageSlug")
     * @JMSS\SerializedName("pageSlug")
     * @var string
     */
    protected $pageSlug;

    public function __construct()
    {
        if (empty($this->status)) {
            $this->status = self::STATUS_DEFAULT;
        }

        $this->modules = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->tags = new ArrayCollection();

    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @return boolean - true when the page is the first one (order == 0)
     */
    public function isFirstPage()
    {
        return $this->getPosition() == 0;
    }

    abstract function getPageType();

    public function setCurrentVersion($currentVersion)
    {
        $this->currentVersion = $currentVersion;
    }

    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }

    public function setNextVersion($nextVersion)
    {
        $this->nextVersion = $nextVersion;
    }

    public function getNextVersion()
    {
        return $this->nextVersion;
    }

    public function getVersion() {
        return $this->version;
    }

    public function setVersion($version) {
        $this->version = $version;
    }

    public function unpublish()
    {
        $this->currentVersion = null;
        $this->nextVersion = null;
        $this->publishedAt = null;
        $this->firstPublishedAt = null;
        $this->setInProgress();
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

    protected function normalizeSlug($slug)
    {
        $categorySlug = '';
        if ($this->getCategory()) {
            $categorySlug = $this->getCategory()->getSlug();
        }
        $slugParts = explode('/', $slug);
        $slug = end($slugParts);
        $slug = preg_replace('/^' . preg_quote($categorySlug) . '[\/-]+/', '', $slug);

        return trim($categorySlug . '/' . $slug, '/');
    }

    public function getPageSlug() {
        if ($this->pageSlug) {
            return $this->pageSlug;
        }
        $slug = $this->getSlug();
        $slugPieces = explode('/', $slug);

        return end($slugPieces);
    }

    public function setPageSlug($pageSlug) {
        if (!trim($pageSlug)) {
            $this->pageSlug = null;
            //because Gedmo\Sluggable\SluggableListener (re)composes the slug only if the reflected value is null (SluggableListener::generateSlug @ line 281)
            $this->slug = null;
            return;
        }
        $this->pageSlug = $pageSlug;
        $this->setSlug($pageSlug);
    }



    public function getTagsIds()
    {
        $tags = $this->getTags();
        if (empty($tags)) {
            return array();
        }

        $ids = array();
        foreach($tags as $tag) {
            $ids[] = $tag->getId();
        }

        return $ids;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @todo: should be made non-static in all projects
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Page $instance
     * @return string
     */
    static public function getFormType(Page $instance = null)
    {
        return static::FORM_TYPE;
    }

    /**
     * @JMSS\Exclude
     */
    private $defaultTitleFormat = "F j, Y, g:i a";

    /**
     * The factory to create the PageEditorModuleCollection out of this entity's modules
     * @var PageEditorModuleCollectionFactory
     * @JMSS\Exclude()
     */
    protected $pageEditorModuleCollectionFactory;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", name="created_at")
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("DateTime")
     */
    private $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", name="updated_at")
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("DateTime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime", name="published_at", nullable=true)
     * @JMSS\Type("DateTime")
     * @JMSS\Groups({"list", "edit", "version"})
     */
    private $publishedAt;

    /**
     * @ORM\Column(type="datetime", name="next_published_at", nullable=true)
     * @JMSS\Type("DateTime")
     * @JMSS\Groups({"list", "edit"})
     */
    private $nextPublishedAt;
    
    /**
     * @ORM\Column(type="datetime", name="first_published_at", nullable=true)
     * @JMSS\Type("DateTime")
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Since("0.2")
     */
    private $firstPublishedAt;
    

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="form.error.article.title.notblank")
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("string")
     */
    private $title = null;

    /**
     * @ORM\Column(type="json_array", name="images", nullable=true)
     * @JMSS\Groups({"edit", "version"})
     * @JMSS\Type("array<integer>")
     */
    private $images;
    
    /**
     * @JMSS\Groups({"gallery"})
     * @JMSS\Type("array<Wf\Bundle\CmsBaseBundle\Entity\Image>")
     * @JMSS\Accessor(getter="getGalleryImages")
     */
    private $galleryImages;

    /**
     * @ORM\Column(type="json_array", name="videos", nullable=true)
     * @JMSS\Groups({"edit", "version"})
     * @JMSS\Type("array<integer>")
     */
    private $videos;
    
    /**
     * @JMSS\Groups({"gallery"})
     * @JMSS\Type("array<Wf\Bundle\CmsBaseBundle\Entity\Video>")
     * @JMSS\Accessor(getter="getGalleryVideos")
     */
    private $galleryVideos;

    /**
     * @ORM\Column(type="json_array", name="audios", nullable=true)
     * @JMSS\Groups({"edit", "version"})
     * @JMSS\Type("array<integer>")
     */
    private $audios;

    /**
     * js code needed to initialize/run modules on public view mode
     *
     * @ORM\Column(type="json_array", name="javascripts", nullable=true)
     * @JMSS\Groups({"edit", "version"})
     * @JMSS\Type("array<string>")
     */
    private $javascripts;

    /**
     * css styles on public view mode
     *
     * @ORM\Column(type="json_array", name="styles", nullable=true)
     * @JMSS\Groups({"edit", "version"})
     * @JMSS\Type("array<string>")
     */
    private $styles;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @JMSS\Groups({"list", "edit", "version"})
     * @Assert\Choice(choices={Page::STATUS_PROGRESS, Page::STATUS_VERIFIED, Page::STATUS_READY, Page::STATUS_PUBLISHED}, message="form.error.article.status.notachoice")
     * @JMSS\Type("string")
     */
    private $status = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @JMSS\Groups({"list", "edit"})
     * @JMSS\Type("integer")
     */
    private $position = null;

    /**
     * @ORM\Column(type="string", length=255, name="template", nullable=true)
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("string")
     */
    private $template = 'default';

    /**
     * @ORM\Column(type="json_array", length=65535, name="settings", nullable=true)
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("array<string, string>")
     * @JMSS\XmlMap
     */
    private $settings = array();

    /**
     * @JMSS\Groups({"edit"})
     * @JMSS\Type("array")
     * @JMSS\Accessor(getter="getAllowedModules")
     */
    private $allowedModules = array();

    /**
     * @JMSS\Groups({"edit"})
     * @JMSS\Type("array")
     * @JMSS\Accessor(getter="getNewModules")
     */
    private $newModules = array();

    /**
     * @ORM\Column(type="json_array", name="modules", nullable=true)
     * @JMSS\Groups({"version"})
     * @JMSS\Type("string")
     * @JMSS\Accessor(getter="getSerializedModules",setter="setSerializedModules")
     */
    private $modules = array();

    /**
     * @var PageEditorModuleCollection
     * @JMSS\Exclude
     */
    private $modulesCollection;

    /**
     * @ORM\Column(type="json_array", length=65535, name="seo", nullable=true)
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("array<string, string>")
     * @JMSS\XmlMap
     */
    private $seo = array();

    /**
     * @ORM\Column(type="string", length=255, name="signature", nullable=true)
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("string")
     */
    private $signature;

    /**
     * @ORM\Column(type="string", length=255, name="epigraph", nullable=true)
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("string")
     */
    private $epigraph;

    /**
     * @ORM\Column(type="string", length=255, name="excerpt", nullable=true)
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("string")
     */
    private $excerpt;

    /**
     * @JMSS\Groups({"list", "edit"})
     * @JMSS\Accessor(getter="getFormType")
     * @JMSS\Type("string")
     */
    private $formType;

    /**
     * @ORM\Column(type="json_array", name="related", nullable=true)
     * @JMSS\Groups({"edit", "list"})
     * @JMSS\Type("array")
     * @var array
     */
    private $related;

    /**
     * @JMSS\Groups({"version"})
     * @JMSS\Type("string")
     * @JMSS\Accessor(getter="getSerializedRelated", setter="setSerializedRelated")
     * @var string
     */
    private $serializedRelated;

    /**
     * @JMSS\Exclude
     * @var boolean
     */
    protected $needsRedirect = false;

    /**
     * @JMSS\Groups({"edit"})
     * @JMSS\Type("array<Wf\Bundle\CmsBaseBundle\Entity\PageVersion>")
     * @var \Wf\Bundle\CmsBaseBundle\Entity\PageVersion[]
     */
    private $lastVersions;

    /**
     * @ORM\Column(name="source_id", type="string", length=255, nullable=true)
     * @JMSS\Groups({"show"})
     */
    private $sourceId;

    /**
     * @ORM\Column(name="source", type="string", length=255, nullable=true)
     * @JMSS\Groups({"show"})
     */
    private $source;

    /**
     * @JMSS\Exclude
     * @var array
     */
    private $analytics = array();

    /**
     * @JMSS\Groups({"show", "list"})
     * @JMSS\Type("string")
     */
    protected $contentType = 'page';

    public function hasDefaultSlug()
    {
        $slug = $this->getSlug();
        //TODO: a better way to detect if it's default as the page is saved with the module's default value and previous algoritm isn't making sense
        return (bool)preg_match('/^(january|february|march|april|may|june|july|august|september|october|november|december)-[0-9]{1,2}-[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}-(am|pm)$/i', $slug);
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
     * get deleteAt
     *
     * @return datetime
     */
    public function getDeletedAt() {
        return $this->deletedAt;
    }


    /**
     * set deletedAt
     *
     * @param datetime $deletedAt
     */
    public function setDeletedAt($deletedAt) {
        $this->deletedAt = $deletedAt;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
        if ($this->getStatus() == self::STATUS_PUBLISHED) {
            if (empty($this->publishedAt)) {
                $this->setPublishedAt(new \DateTime());
            }
        } else {
            $this->setPublishedAt(null);
        }
    }

    public function __toString()
    {
        return $this->getTitle();
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function isInProgress() {
        return $this->getStatus() == self::STATUS_PROGRESS;
    }

    public function isPublished()
    {
        return $this->getStatus() == self::STATUS_PUBLISHED;
    }

    public function isPublishedNow()
    {
        $publishedAt = $this->getPublishedAt();
        if (!$publishedAt) {
            return false;
        }
        $now = new \DateTime();
        return $this->isPublished() && $publishedAt <= $now;
    }

    public function isVerified()
    {
        return $this->getStatus() == self::STATUS_VERIFIED;
    }

    public function isReady() {
        return $this->getStatus() == self::STATUS_READY;
    }

    public function setInProgress()
    {
        $this->setStatus(self::STATUS_PROGRESS);
    }

    public function setVerified()
    {
        $this->setStatus(self::STATUS_VERIFIED);
    }

    public function setReady()
    {
        $this->setStatus(self::STATUS_READY);
    }

    public function setPublished()
    {
        $this->setStatus(self::STATUS_PUBLISHED);
    }

    public function setTemplate($template)
    {
        $this->template = $template;
        $this->getModulesCollection()->setTemplate($this->getTemplate());
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getContentType()
    {
        $template = $this->getTemplate();
        switch ($template) {
            case 'default':
                return 'news';
            default:
                return $template;
        }
    }


    /**
     * get settings
     *
     * @return object
     */
    public function getSettings() {
        return $this->settings;
    }

    /**
     * set settings
     *
     * @param object|array $settings
     */
    public function setSettings($settings) {
       $this->settings = $settings;
    }


    public function getTemplateName()
    {
        return $this->getTemplate();
    }

    protected function cleanHTML($string)
    {
        //replace <br /> with a space to prevent concatenating words on separate lines when stripping tags
        $string = preg_replace('@<br( /)?>@i', " ", $string);
        $string = strip_tags($string);
        $string = html_entity_decode($string);
        //clear double spaces
        $string = preg_replace("@\s+@u", " ", $string);
        $string = trim($string, " \n");

        return $string;
    }

    protected function _setTitle($title)
    {
        $title = $this->cleanHTML($title);

        //set the seo title if seo title is the same as title (meaning not manually set) or if it's empty
        $currentTitle = trim($this->getTitle());
        $currentSeoTitle = trim($this->getSeoTitle());
        $isTheSame = $currentSeoTitle == $currentTitle || empty($currentSeoTitle);
        if ($isTheSame) {
            $this->setSeoTitle($title);
        }

        $currentTitle = SlugUtil::normalizeSlug($currentTitle, static::STOP_WORDS, ', ');
        $currentSeoKeywords = trim($this->getSeoKeywords());
        $isTheSame = $currentTitle == $currentSeoKeywords || empty($currentSeoKeywords);
        if ($isTheSame) {
            $this->setSeoKeywords($currentTitle);
        }

        if (empty($title)) {
            $this->setDefaultTitle();
            return;
        }

        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getHTML()
    {
        return $this->getModulesCollection()->getHTML();
    }

    public function setImages($images)
    {
    }

    /**
     * @param array $images - it should always be an array of Image objects
     * there's a check done for backwards compatible, in case it receives an array of image ids
     */
    protected function _setImages($images)
    {
        $imageIds = array();
        foreach ($images as $image) {
            if ($image instanceof Image) {
                $imageIds[] = $image->getId();
            } else {
                $imageIds[] = $image;
            }
        }
        $this->images = $imageIds;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function setVideos($videos)
    {
    }

    /**
     * set videos
     *
     * @param object|array $settings
     */
    protected function _setVideos($videos)
    {
        $this->videos = $videos;
    }

    /**
     * get videos
     *
     * @return object
     */
    public function getVideos()
    {
        return $this->videos;
    }

    public function setAudios($audios)
    {
    }

    /**
     * set audios
     *
     * @param object|array $settings
     */
    protected function _setAudios($audios)
    {
        $this->audios = $audios;
    }

    /**
     * get audios
     *
     * @return object
     */
    public function getAudios()
    {
        return $this->audios;
    }

    public function setJavascripts($javascripts)
    {
        $this->javascripts = $javascripts;
    }

    public function getJavascripts()
    {
        return $this->javascripts;
    }

    public function setStyles($styles)
    {
        $this->styles = $styles;
    }

    public function getStyles()
    {
        return $this->styles;
    }

    public function setDefaultTitle()
    {
        $dt = new \DateTime();
        $this->title = $dt->format($this->defaultTitleFormat);
    }

    public function getTypeName()
    {
        return $this->getPageType();
    }

    public function getFullTemplatePath() {
        $templateName = $this->getTemplateName();
        $type = $this->getTypeName();

        $dir = ucfirst($type);

        // if (empty($this->bundleName)) {
        //     throw new \Exception('You forgot to overwrite the Page::$bundleName property');
        // }

        $paths = array(
            'WfCmsBaseBundle',
            'Template'
            );

        if (strpos($templateName, '/') === false && isset($dir)) {
            array_push($paths, $dir . '/' . $templateName);
        } else {
            array_push($paths, ucfirst($templateName));
        }

        return implode(':', $paths);
    }

    public function getFullEditorTemplatePath() {
        //both the editor and the static html use the same template
        return $this->getFullTemplatePath();
    }

    public function getJSEditorTemplatePath() {
        $templateName = $this->getTemplateName();
        if (strpos($templateName, '/') === false) {
            return $this->getTypeName() . '/' . $templateName;
        } else {
            return lcfirst($templateName);
        }
    }

    public function getAllowedModules()
    {
        $metadata = $this->getMetadata();

        return $metadata ? $metadata->getAllowedModules() : array();
    }

    public function getNewModules()
    {
        $metadata = $this->getMetadata();

        return $metadata ? $metadata->getNewModules(): array();
    }

    public function setModules($modules)
    {
        if ($modules instanceof PageEditorModuleCollection) {
            $this->modulesCollection = $modules;
        } else {
            $this->modules = $modules;
            unset($this->modulesCollection); //make sure it's recreated on the next request
        }

        $this->syncModules();
    }

    public function setTitle($title)
    {
        $title = strip_tags($title);
        $this->_setTitle($title);
    }

    /**
     * Called whenever the modulesCollection changes
     */
    protected function syncModules()
    {
        $modulesCollection = $this->getModulesCollection();

        $javascripts = $modulesCollection->getJavascripts();
        $styles = $modulesCollection->getStyles();

        $this->setJavascripts($javascripts);
        $this->setStyles($styles);

        $this->modules = $modulesCollection->toArray();
    }

    public function getModules()
    {
        if (is_string($this->modules)) {
            //this happens when cloning a version. Doctrine doesn't (really) allow to use __clone
            $this->modules = json_decode($this->modules, true);
            if (is_null($this->modules)) {
                $this->modules = array();
            }
        }

        return $this->modules;
    }

    /**
     * @return PageEditorModuleCollection
     */
    public function getModulesCollection()
    {
        if (empty($this->modulesCollection)) {
            $this->modulesCollection = $this->pageEditorModuleCollectionFactory->create($this->getModules());
            $this->modulesCollection->setTemplate($this->getTemplate());

            if (isset($this->category) && !is_null($this->category)) {
                $this->modulesCollection->setCategory($this->category);
            }
        }

        return $this->modulesCollection;
    }

    public function updateModules($modulesData)
    {
        $ret = $this->getModulesCollection()->updateModules($modulesData);
        $this->syncModules();

        return $ret;
    }

    public function removeModules($deletedIds)
    {
        $this->getModulesCollection()->removeModules($deletedIds);
        $this->syncModules();
    }

    public function clearModules()
    {
        $this->getModulesCollection()->clear();
    }

    public function setPageEditorModuleCollectionFactory($pageEditorModuleCollectionFactory)
    {
        $this->pageEditorModuleCollectionFactory = $pageEditorModuleCollectionFactory;
    }

    public function setPublishedAt($publishedAt = null)
    {
        if (is_string($publishedAt)) {
            $publishedAt = new \DateTime($publishedAt);
        }

        $this->publishedAt = $publishedAt;
        if (!is_null($publishedAt)) {
            $this->setStatus(self::STATUS_PUBLISHED);
            if (empty($this->firstPublishedAt)) {
                $this->setFirstPublishedAt($publishedAt);
            }
        }
    }

    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    public function setNextPublishedAt($nextPublishedAt)
    {
        $this->nextPublishedAt = $nextPublishedAt;
    }

    public function getNextPublishedAt()
    {
        return $this->nextPublishedAt;
    }

    public function getSerializedModules()
    {
        return json_encode($this->modules);
    }

    public function setSerializedModules($modulesString)
    {
        $this->modules = json_decode($modulesString, true);
    }

    public function getSerializedAllowedModules()
    {
        return json_encode($this->getAllowedModules());
    }

    public function setSerializedAllowedModules($allowedModulesString)
    {//BC
    }

    public function getSerializedNewModules()
    {
        return json_encode($this->getNewModules());
    }

    public function setSerializedNewModules($newModulesString)
    {//BC
    }

    public function getSeo()
    {
        return $this->seo;
    }

    public function setSeo($seo)
    {
        $this->seo = $seo;
    }

    public function getSeoTitle()
    {
        $seo = $this->getSeo();
        return isset($seo['title']) ? $seo['title'] : '';
    }

    public function setSeoTitle($seoTitle)
    {
        $seoTitle = $this->cleanHTML($seoTitle);
        $seo = $this->getSeo();
        $seo['title'] = !empty($seoTitle) ? $seoTitle : $this->getTitle();
        $this->setSeo($seo);
    }

    public function getSeoDescription()
    {
        $seo = $this->getSeo();
        return isset($seo['description']) ? $seo['description'] : '';
    }

    public function setSeoDescription($seoDescription)
    {
        $seoDescription = $this->cleanHTML($seoDescription);
        $seo = $this->getSeo();
        $seo['description'] = !empty($seoDescription) ? $seoDescription : '';
        $this->setSeo($seo);
    }

    public function getSeoKeywords()
    {
        $seo = $this->getSeo();
        return isset($seo['keywords']) ? $seo['keywords'] : '';
    }

    public function setSeoKeywords($seoKeywords)
    {
        $seo = $this->getSeo();
        $seo['keywords'] = !empty($seoKeywords) ? $seoKeywords : SlugUtil::normalizeSlug($this->getTitle(), static::STOP_WORDS, ', ');
        $this->setSeo($seo);
    }


    public function getEpigraph()
    {
        return $this->epigraph;
    }

    public function setEpigraph($epigraph)
    {
        $this->epigraph = $epigraph;
    }

    public function _setEpigraph($epigraph)
    {
        $this->epigraph = $epigraph;
    }

    public function getExcerpt()
    {
        return $this->excerpt;
    }

    public function setExcerpt($excerpt)
    {
        $this->excerpt = $excerpt;
    }

    public function getSignature()
    {
        return $this->signature;
    }

    public function setSignature($signature)
    {
        $this->signature = $signature;
    }

    protected function _setSignature($signature)
    {
        $this->signature = $signature;
    }

    public function getCommentsSetting()
    {
        $settings = $this->getSettings();
        return isset($settings['comments']) ? $settings['comments'] : static::SETTING_COMMENTS_DEFAULT;
    }

    public function setCommentsSetting($commentsSetting)
    {
        $settings = $this->getSettings();
        $settings['comments'] = $commentsSetting;
        $this->setSettings($settings);
    }

    public function getVotableSetting()
    {
        $settings = $this->getSettings();
        return isset($settings['votable']) ? $settings['votable'] : static::SETTING_VOTABLE_DEFAULT;
    }

    public function setVotableSetting($votableSetting)
    {
        $settings = $this->getSettings();
        $settings['votable'] = $votableSetting;
        $this->setSettings($settings);
    }

    public function isIndexable()
    {
        return false;
    }

    public function getRelatedData()
    {
        return array(
            'id' => $this->getId(),
            'type' => 'page',
            'slug' => $this->getSlug(),
            'title' => $this->getTitle(),
            'preview_url' => $this->getModulesCollection()->getRelatedUrl($this),
            'category' => array(
                'id' => $this->getCategory()->getId(),
                'slug' => $this->getCategory()->getSlug(),
            )
        );
    }

    protected function _setRelated($related)
    {
        $relatedData = array();
        foreach($related as $relation) {
            if (!$relation) {
                //sometimes it's null
                continue;
            }
            $relatedData[] = $relation->getRelatedData();
        }

        $this->related = $relatedData;
    }

    public function getRelated()
    {
        return $this->related;
    }

    public function getRelatedPagesIds()
    {
        $related = $this->getRelated();
        if (!$related) {
            return array();
        }

        $ids = array();
        array_walk($related, function($relatedData) use (&$ids) {
            if (!isset($relatedData['type']) || (isset($relatedData['type']) && $relatedData['type'] == 'page')) {
                $ids[] = $relatedData['id'];
            }
        });

        return $ids;
    }

    public function getRelatedIds()
    {
        $related = $this->getRelated();
        if (!$related) {
            return array();
        }

        $ids = array();
        array_walk($related, function($relatedData) use (&$ids) {
            $ids[] = $relatedData['id'];
        });

        return $ids;
    }

    public function setRelated($related)
    {
        $this->related = $related;
    }

    public function getSerializedRelated() {
        return json_encode($this->getRelated());
    }

    public function setSerializedRelated($serializedRelated) {
        $this->related = json_decode($serializedRelated, true);
    }

    /**
     * sets a flag so that handlers know that they must redirect first
     * if no param is specified returns the flag
     * @param boolean $flag
     * @return boolean
     */
    public function needsRedirect($flag = null)
    {
        if (!is_null($flag)) {
            $this->needsRedirect = (bool)$flag;
        }
        return $this->needsRedirect;
    }

    public function getLastVersions()
    {
        return $this->lastVersions;
    }

    public function setLastVersions($lastVersions)
    {
        $this->lastVersions = $lastVersions;
    }

    public function getPageViews()
    {
        return $this->getAnalytics('pageviews');
    }

    public function setPageViews($pageViews)
    {
        return $this->setAnalytics('pageviews', $pageViews);
    }

    public function getAnalytics($type)
    {
        if (isset($this->analytics[$type])) {
            return $this->analytics[$type];
        }
    }

    public function setAnalytics($type, $value)
    {
        $this->analytics[$type] = $value;

        return $this;
    }

    public function getSourceId()
    {
        return $this->sourceId;
    }

    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    public function addTag(Tag $tag)
    {
        $this->collectionAdd('tags', $tag);
    }

    public function removeTag(Tag $tag)
    {
        $this->collectionRemove('tags', $tag);
    }

    public function setSimpleTags($tags)
    {
        return $this->setTypeTags($tags, null);
    }

    public function getSimpleTags()
    {
        $tags = $this->getTypeTags(null);

        return $tags;
    }

    public function setTypeTags($typeTags, $type)
    {
        $accessor = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor();
        $allTags = array();
        $existingTypeTags = $this->getTypeTags($type);
        $tags = $accessor->getValue($this, 'tags');
        $tags = method_exists($tags, 'toArray') ? $tags->toArray() : (array)$tags;//doctrine collection or array
        foreach($tags as $tag) {
            if (!in_array($tag, $existingTypeTags)) {
                $allTags[] = $tag;
            }
        }
        foreach ($typeTags as $tag) {
            if ($tag->getType() != $type) {
                //DON'T change the type here. If an article1 adds tag X as a simple tag and article2 chooses
                //X as the title for a section tag, this would turn X into a section tag "globally" (for article1 as well)
                //This should never happen, the TaggableFormType should now return the tags with the correct type
                //Maybe it'll happen for pageVersions created before this was fixed, don't throw an exception either :D
                //$tag->setType($type);
                error_log(sprintf('[TagTypeMismatch]Trying to add tag %d (%s) with type %s, it came with type %s',
                        $tag->getId(), $tag->getTitle(), $type, $tag->getType()
                ));
            }
            $allTags[] = $tag;
        }

        $accessor->setValue($this, 'tags', $allTags);
    }

    public function getTypeTags($type)
    {
        $tags = array();

        $allTags = $this->getTags();
        if ($allTags) {
            foreach ($allTags as $tag) {
                if($tag->getType() == $type) {
                    $tags[] = $tag;
                }
            }
        }

        return $tags;
    }

    public function getCategorySlug()
    {
        $category = $this->getCategory();
        if ($category) {
            return $category->getSlug();
        }

        return $category;
    }

    /**
     * setParagraphs to the article
     * @param array $paragraphs array with the paragraphs to be added
     */
    public function setParagraphs($paragraphs)
    {
        $this->getModulesCollection()->setParagraphs($paragraphs);
        $this->syncModules();
    }

    public function setImageCaptioned($image)
    {
        $this->getModulesCollection()->setImageCaptioned($image);
        $this->syncModules();
    }

    public function getEmbedded($type)
    {
        $items = array();
        $this->getModulesCollection()->walkModulesTree(function($moduleData) use (&$items, $type) {
            if (isset($moduleData['data'][$type]['id'])) {
                $items[] = $moduleData['data'][$type]['id'];
            }
        });

        return array_unique($items);
    }

    public function setCategory($category)
    {
        if ($this->category instanceof Category && $category instanceof Category
            && $this->category->getId() != $category->getId()) {
            //reset slug when category has changed
            $this->setSlug(null);
        }

        if (!is_null($this->category)) {
            if ($this->category == $category) {
                //trying to set same category
                return;
            }

            $this->_removeCategory($this->category);
        }

        $this->category = $category;
        $this->ensureCategoryInCategories();
    }

    protected function ensureCategoryInCategories()
    {
        $this->_addCategory($this->category);
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    public function getCategories()
    {
        if (empty($this->categories)) {
            $this->categories = new ArrayCollection();
        }
        
        return $this->categories;
    }
    
    public function getSearchCategories()
    {
        $categories = $this->getCategories();
        
        //normalize array as it gets tranformed in a non zero-based index array which is seen as an object by fos elastica
        //transformation is done when the object is persisted (ArrayCollection get's replaced with PersistentCollection which is not zero based)
        return array_values(method_exists($categories, 'toArray') ? $categories->toArray() : (array)$categories);        
    }

    /**
     * return all categories and all parents categories of those categories
     * @return type
     */
    public function getAllCategories()
    {
        $categories = $this->getCategories();
        $categories = $categories ?: array();
        $all = array_merge(method_exists($categories, 'toArray') ? $categories->toArray() : (array)$categories);
        foreach($categories as $category) {
            $all = array_merge($all, $x = $category->getParents());
        }

        $unique = array();
        foreach($all as $category) {
            if (!empty($category) && is_object($category) && !isset($unique[$category->getId()])) {
                $unique[$category->getId()] = $category;
            }
        }

        return array_values($unique);
    }

    public function setCategories($categories)
    {
        $this->categories = $categories;
        $this->ensureCategoryInCategories();
    }

    protected function _addCategory($category)
    {
        $this->collectionAdd('categories', $category);
    }

    protected function _removeCategory($category)
    {
        $this->collectionRemove('categories', $category);
    }

    public function addCategory($category)
    {
        $this->_addCategory($category);
        $this->ensureCategoryInCategories();
    }

    public function removeCategory($category)
    {
        $this->collectionRemove('categories', $category);
        $this->ensureCategoryInCategories();
    }

    public function getAuthors()
    {
        return $this->authors;
    }

    public function setAuthors($authors)
    {
        $this->authors = $authors;
    }

    public function addAuthor($author)
    {
        $this->collectionAdd('authors', $author);
    }

    public function removeAuthor($author)
    {
        $this->collectionRemove('authors', $author);
    }

    /**
     * Set author
     * @deprecated since version 20131218042234
     */
    public function setAuthor(User $author = null)
    {
        $this->collectionAdd('authors', $author);
    }

    /**
     * Get author
     * @deprecated since version 20131218042234
     */
    public function getAuthor()
    {
        if (!empty($this->authors) && (is_array($this->authors) || $this->authors instanceof \Traversable)) {
            foreach($this->authors as $author) {
                return $author;
            }
        }

        return null;
    }

    public function getAuthorName()
    {
        $author = $this->getAuthor();
        if (!empty($author))
            return $author->getName();
        return '';
    }

    public function getAuthorId()
    {
        if (!is_null($this->getAuthor())) {
            return $this->getAuthor()->getId();
        }

        return null;
    }

    public function __call($methodName, $arguments)
    {
        if (preg_match('@^set(.*)@', $methodName, $matches)) {
            try {
                $ret = call_user_func_array(array($this->getModulesCollection(), $methodName), $arguments);
                $this->syncModules();

                return $ret;
            } catch (\Exception $e) {
                throw new \Exception(sprintf('set method %s not found on Page nor on PageEditorModuleCollection', $methodName));
            }
        }

        throw new \Exception(sprintf('Call to undefined method %s on Page', $methodName));
    }

    public function getPublisher()
    {
        return $this->publisher;
    }

    public function setPublisher(User $publisher = null)
    {
        $this->publisher = $publisher;
    }

    public function getMetadataChecksum()
    {
        $metadata = $this->getMetadata();

        if (empty($metadata)) {
            return null;
        }

        return $metadata->getChecksum();
    }

    protected function collectionAdd($field, $value)
    {
        if (empty($value)) {
            return;
        }

        if (empty($this->{$field})) {
            $this->{$field} = new ArrayCollection();
        }

        $found = false;
        foreach($this->{$field} as $existingValue) {
            if ($existingValue->getId() == $value->getId()) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->{$field}[] = $value;
        }
    }

    protected function collectionRemove($field, $value)
    {
        if (empty($this->{$field})) {
            return;
        }

        if (is_object($this->{$field})) {
            $key = $this->{$field}->indexOf($value);
            if ($key !== false) {
                $this->{$field}->remove($key);
            }
        } else {
            $key = array_search($value, $this->{$field});
            if ($key !== false) {
                unset($this->{$field}[$key]);
            }
        }
    }


    public function setCreator($creator)
    {
        if ($creator) {
            $this->creator = $creator;
        }
    }

    public function getCreator()
    {
        return $this->creator;
    }

    public function getRenderer()
    {
        return self::RENDERER_DEFAULT;
    }

    public function getShortDescriptionFromContent() {
        $content = $this->getModulesCollection()->getTextHTML();
        $content = $this->cleanHTML($content);

        if(preg_match('/^.{1,250}\b/s', $content, $match)) {
            return $match[0];
        }
        return '';

    }
    
    public  function getFirstPublishedAt()
    {
        return $this->firstPublishedAt;
    }
    
    public  function setFirstPublishedAt($firstPublishedAt)
    {
        if (!empty($firstPublishedAt)) {
            if (is_string($firstPublishedAt)) {
                $firstPublishedAt = new \DateTime($firstPublishedAt);
            }
            $this->firstPublishedAt = $firstPublishedAt;
        }
    }
    
    public function getGalleryImages()
    {
        return $this->getModulesCollection()->getGalleryImages();//only images from the module with role "gallery"
        //return $this->getModulesCollection()->getImagesObjects();//all images
    }
    
    public function getGalleryVideos()
    {
        return $this->getModulesCollection()->getGalleryVideos();
        //return $this->getModulesCollection()->getVideosObjects();
    }
}
