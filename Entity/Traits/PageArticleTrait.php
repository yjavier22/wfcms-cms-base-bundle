<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMSS;
use Wf\Bundle\CmsBaseBundle\Entity\Page;

/**
 */
trait PageArticleTrait
{
    /**
     * @ORM\Column(type="string", length=255, name="first_title", nullable=true)
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("string")
     */
    protected $firstTitle = null;

    /**
     * @ORM\Column(type="string", length=1000, name="short_description", nullable=true)
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("string")
     */
    protected $shortDescription = null;

    /**
     * @ORM\Column(type="json_array", length=65000)
     * @JMSS\Groups({"edit", "version"})
     * @JMSS\Type("string")
     * @JMSS\Accessor("getSerializedContent")
     */
    protected $content = null;

    /**
     * @ORM\Column(type="string", length=1000, name="images", nullable=true)
     * @JMSS\Groups({"edit", "version"})
     * @JMSS\Type("array<integer>")
     */
    protected $images;

    /**
     * @ORM\OneToOne(targetEntity="Wf\Bundle\CmsBaseBundle\Entity\Image", cascade={"persist"})
     * @ORM\JoinColumn(name="main_image_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * @JMSS\Type("Wf\Bundle\CmsBaseBundle\Entity\Image")
     * @JMSS\Groups({"edit", "list", "version"})
     */
    protected $mainImage;

    /**
     * @JMSS\Groups({"edit", "list"})
     * @JMSS\Type("string")
     */
    protected $previewUrl;

    /**
     * @ORM\Column(type="boolean")
     * @JMSS\Groups({"edit", "list"})
     * @JMSS\Type("boolean")
     */
    protected $hasImages = false;

    /**
     * @ORM\Column(type="boolean")
     * @JMSS\Groups({"edit", "list"})
     * @JMSS\Type("boolean")
     */
    protected $hasVideos = false;

    /**
     * @ORM\Column(type="boolean")
     * @JMSS\Groups({"edit", "list"})
     * @JMSS\Type("boolean")
     */
    protected $hasAudios = false;

    /**
     * @ORM\Column(type="date", name="date_edition", nullable=true)
     * @JMSS\Type("DateTime")
     * @JMSS\Groups({"list", "edit"})
     */
    protected $dateEdition = null;

    /**
     * @var string $paperCategory
     *
     * @ORM\Column(name="paper_category", type="string", length=255)
     * @JMSS\Expose
     * @JMSS\Type("string")
     * @JMSS\Groups({"edit", "list"})
     */
    protected $paperCategory;

    /**
     * @ORM\Column(type="boolean")
     * @JMSS\Groups({"list", "edit"})
     * @JMSS\Type("boolean")
     */
    protected $highlight = false;
    
    /**
     * @JMSS\Groups({"list", "edit"})
     * @JMSS\Type("string")
     * @JMSS\Accessor(getter="getFormattedPublishedAt")
     */
    protected $formattedPublishedAt;

    protected function syncModules()
    {
        parent::syncModules();

        $modulesCollection = $this->getModulesCollection();

        $title = $modulesCollection->getTitleContent();
        $images = $modulesCollection->getImagesObjects();
        $videos = $modulesCollection->getVideosIds();
        $related = $modulesCollection->getRelatedObjects();
        //$modulesCollection->updateTags();
        $content = $modulesCollection->getHTML();
        $signature = $modulesCollection->getSignatureContent();

        $this->_setTitle($title);
        $this->_setImages($images);
        $this->_setVideos($videos);
        $this->_setRelated($related);
        $this->_setSignature($signature);

        $this->setFirstTitle($modulesCollection->getSupraContent());
        $this->setShortDescription($modulesCollection->getEpigraphContent());
        $this->setContent($content);
        //this never gets updated so there is no chance it will update the module contents
        $modulesCollection->setFirstPublishedAt($this->getFormattedDate($this->getFirstPublishedAt()));
    }

    public function setTitle($title)
    {
        parent::setTitle($title);
        $this->getModulesCollection()->setTitle($title);
        $this->syncModules();
    }

    public function setSupra($supra)
    {
        $this->getModulesCollection()->setSupra($supra);
        $this->syncModules();
    }

    public function setEpigraph($epigraph)
    {
        $this->getModulesCollection()->setEpigraph($epigraph);
        $this->syncModules();
    }

    /**
     * addParagraphs to the article
     * @param array $paragraphs array with the paragraphs to be added
     */
    public function addParagraphs($paragraphs)
    {
        $this->getModulesCollection()->addParagraphs($paragraphs);
        $this->syncModules();
    }

    /**
     * Add text content to the article. The text content can be either paragraph or subtitle (or other allowed modules)
     * @param array $textContent array of arrays to be added.
     * 		Each array must have a 'text' key. It could have a 'module' key, including the type of module (paragraph, subtitle, etc)
     */
    public function addTextContent($textContent)
    {
        $this->getModulesCollection()->addTextContent($textContent);
        $this->syncModules();
    }

    public function getTextHTML()
    {
        return $this->getModulesCollection()->getTextHTML();
    }

    public function setRelated($related) {
        parent::setRelated($related);
        $this->getModulesCollection()->setRelated($related);
        $this->syncModules();
    }

    public function setImages($images)
    {
        parent::setImages($images);
        $this->getModulesCollection()->setImages($images);
        $this->syncModules();
    }

    /**
     * @see Wf\Bundle\CmsBaseBundle\Entity\Page::_setImages()
     */
    protected function _setImages($images)
    {
        parent::_setImages($images);

        $this->_setHasImages();

        if (empty($this->mainImage)) {
            $mainImage = $this->getModulesCollection()->getMainImage();
            if ($mainImage) {
                $this->mainImage = $mainImage;
            } else {
                $this->mainImage = array_shift($images);
            }
        } else {
            //if it is set but not in the new list then reset it; fixes issue #5661
            $imageIds = $this->getModulesCollection()->getImagesIds();
            if (!in_array($this->mainImage->getId(), $imageIds)) {
                $this->mainImage = array_shift($images);
            }
        }
    }

    protected function _setHasImages()
    {
        $gallery = $this->getModulesCollection()->getGallery();
        if (!empty($gallery)) {
            $this->hasImages = true;
        } else {
            $this->hasImages = false;
        }
    }

    public function setVideos($videos)
    {
        parent::setVideos($videos);

        $this->getModulesCollection()->setVideos($videos);
        $this->syncModules();
    }

    protected function _setVideos($videos)
    {
        parent::_setVideos($videos);

        if (count($videos)) {
            $this->hasVideos = true;
        } else {
            $this->hasVideos = false;
        }
    }

    protected function _setAudios($audios)
    {
        parent::_setAudios($audios);

        if (count($audios)) {
            $this->hasAudios = true;
        }
    }

    public function getImagesObjects()
    {
        return $this->getModulesCollection()->getImagesObjects();
    }

    public function getVideosObjects()
    {
        return $this->getModulesCollection()->getVideosObjects();
    }

    public function getAudiosObjects()
    {
        return $this->getModulesCollection()->getAudiosObjects();
    }

    public function getPageType()
    {
        return self::TYPE_ARTICLE;
    }
    
    public function getArticleType()
    {
        return $this->getTemplate();
    }

    public function setMainImage($mainImage)
    {
        $this->mainImage = $mainImage;
    }

    public function getMainImage()
    {
        return $this->mainImage;
    }

    public function isIndexable()
    {
        return true;
    }

    public function setFirstTitle($firstTitle)
    {
        $this->firstTitle = $firstTitle;
    }

    public function getFirstTitle()
    {
        return $this->firstTitle;
    }

    public function setShortDescription($shortDescription)
    {
        $shortDescription = $this->cleanHTML($shortDescription);
        $currentDescription = trim($this->getShortDescription());
        $currentSeoDescription = trim($this->getSeoDescription());
        $isTheSame = $currentDescription == $currentSeoDescription || empty($currentSeoDescription);
        if ($isTheSame) {
            $this->setSeoDescription($shortDescription);
        }
        $this->shortDescription = $shortDescription;
    }

    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    public function setSeoDescription($seoDescription)
    {
        $seoDescription = !empty($seoDescription) ? $seoDescription : $this->getShortDescription();
        parent::setSeoDescription($seoDescription);
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getSerializedContent()
    {
        return json_encode($this->content);
    }

    public function getContentText()
    {
        $ret = '';
        if (empty($this->content) || !is_array($this->content)) {
            return '';
        }
        foreach ($this->content as $selector=>$modules) {
            foreach ($modules as $text) {
                $ret.= $text;
            }
        }

        return $ret;
    }

    public function setPreviewUrl($previewUrl)
    {
        $this->previewUrl = $previewUrl;
    }

    public function getPreviewUrl()
    {
        return $this->previewUrl;
    }

    protected function getFormattedDate($publishedAt)
    {
        if (!empty($publishedAt)) {
            $formatter = new \IntlDateFormatter(\Locale::getDefault(), \IntlDateFormatter::NONE, \IntlDateFormatter::NONE);
            if ($this->publishedAtFormat) {
                $formatter->setPattern($this->publishedAtFormat);
            }
            $formattedPublishedAt = $formatter->format($publishedAt);
        } else {
            $formattedPublishedAt = '';
        }
        
        return $formattedPublishedAt;
    }
    
    public function setPublishedAt($publishedAt = null) {
        parent::setPublishedAt($publishedAt);

        $this->getModulesCollection()->setPublishedAt($this->getFormattedDate($publishedAt));

        $this->syncModules();
    }
    
    public function setFirstPublishedAt($firstPublishedAt)
    {
        parent::setFirstPublishedAt($firstPublishedAt);
        
        $this->getModulesCollection()->setFirstPublishedAt($this->getFormattedDate($firstPublishedAt));
        
        $this->syncModules();
    }

    public function setSignature($signature) {
        parent::setSignature($signature);
        $this->getModulesCollection()->setSignature($signature, $this->getAuthor());
        $this->syncModules();
    }

    public function hasImages()
    {
        return $this->hasImages;
    }

    public function hasVideos()
    {
        return $this->hasVideos;
    }

    public function hasAudios()
    {
        return $this->hasAudios;
    }

    public function setDateEdition($dateEdition)
    {
        $this->dateEdition = $dateEdition;
    }

    public function getDateEdition()
    {
        return $this->dateEdition;
    }

    public function setHighlight($highlight)
    {
        $this->highlight = $highlight;
    }

    public function getHighlight()
    {
        return $this->highlight;
    }

    public function setPaperCategory($paperCategory)
    {
        $this->paperCategory = $paperCategory;
    }

    public function getPaperCategory()
    {
        return $this->paperCategory;
    }


    public function setImportedValues(array $values)
    {
        $this->setTemplate((isset($values['template'])?$values['template']:'default'));

        if (isset($values['title']))            $this->setTitle($values['title']);
        if (isset($values['firstTitle']))       $this->setFirstTitle($values['firstTitle']);
        if (isset($values['supra']))            $this->setSupra($values['supra']);
        if (isset($values['summary']))          $this->setEpigraph($values['summary']);
        if (isset($values['signature']))        $this->setSignature($values['signature']);
        if (isset($values['author']))           $this->setAuthor($values['author']);
        if (isset($values['publicationDate']))  $this->setPublishedAt($values['publicationDate']);
        if (isset($values['category']))         $this->setCategory($values['category']);
        if (isset($values['paragraphs']))       $this->addTextContent($values['paragraphs']);
        if (isset($values['dateEdition']))      $this->setDateEdition($values['dateEdition']);
        if (isset($values['paperCategory']))    $this->setPaperCategory($values['paperCategory']);
        if (isset($values['tags']))             $this->setTags($values['tags']);
    }


    public function getRelatedData() {
        $base = parent::getRelatedData();
        return array_merge($base, array(
            'has_audios' => $this->hasAudios(),
            'has_videos' => $this->hasVideos(),
            'has_images' => $this->hasImages(),
        ));
    }

    static public function getFormType(Page $instance = null)
    {
        if (isset($instance)) {
            $templateName = $instance->getTemplateName();

            switch ($templateName) {
                case 'default':
                    return static::FORM_TYPE;
                default:
                    return $templateName;
            }
        }

        return parent::getFormType();
    }

    public function getBlogTags()
    {
        return $this->getTypeTags('blog');
    }

    public function setBlogTags($tags)
    {
        return $this->setTypeTags($tags, 'blog');
    }

    public function getFormattedPublishedAt()
    {
        $publishedAtModule = $this->getModulesCollection()->getRoledModule('published_at');
        if (!empty($publishedAtModule['data']['content'])) {
            return $publishedAtModule['data']['content'];
        }
        
        return '';
    }
}

