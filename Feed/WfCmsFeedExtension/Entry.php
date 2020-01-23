<?php

namespace Wf\Bundle\CmsBaseBundle\Feed\WfCmsFeedExtension;

use Zend\Feed\Writer\Entry as BaseEntry;

Class Entry extends BaseEntry
{
    /**
     * array of data to be used by renderer
     */
    protected $data;

    public function setSubtitle($subtitle)
    {
        $this->data['subtitle'] = $subtitle;
        return $this;
    }

    public function getSubtitle()
    {
        return $this->data['subtitle'];
    }

    public function setCategory($category)
    {
        $this->data['category'] = $category;
        return $this;
    }

    public function getCategory()
    {
        return $this->data['category'];
    }

    public function setAuthor($author)
    {
        $this->data['author'] = $author;
        return $this;
    }

    public function getAuthor()
    {
        if (!isset($this->data['author'])) {
            return '';
        }

        return $this->data['author'];
    }

    public function addTag($tag)
    {
        $this->data['tags'][] = $tag;
        return $this;
    }

    public function getTags()
    {
        if (!isset($this->data['tags'])) {
            return '';
        }

        return $this->data['tags'];
    }

    public function setMainCategory($cat)
    {
       $this->data['mainCategory'] = $cat;
       return $this;
    }

    public function getMainCategory()
    {
        if (!isset($this->data['mainCategory'])) {
            return '';
        }

        return $this->data['mainCategory'];
    }

    public function setSecondaryCategory($cat)
    {
       $this->data['secondaryCategory'] = $cat;
       return $this;
    }

    public function getSecondaryCategory()
    {
        if (!isset($this->data['secondaryCategory'])) {
            return '';
        }

        return $this->data['secondaryCategory'];
    }

    public function setXMLContent($content)
    {
        $this->data['XMLcontent'] = $content;
        return $this;
    }

    public function getXMLContent()
    {
        if (!isset($this->data['XMLcontent'])) {
            return '';
        }

        return $this->data['XMLcontent'];
    }

    public function setSector($sector)
    {
        $this->data['sector'] = $sector;
        return $this;
    }

    public function getSector()
    {
        if (!isset($this->data['sector'])) {
            return '';
        }

        return $this->data['sector'];
    }

    public function setPage($page)
    {
        $this->data['page'] = $page;
        return $this;
    }

    public function getPage()
    {
        if (!isset($this->data['page'])) {
            return '';
        }

        return $this->data['page'];
    }

    public function setPublishedDate($pubDate)
    {
        $this->data['pubDate'] = $pubDate;
        return $this;
    }

    public function getPublishedDate()
    {
        if (!isset($this->data['pubDate'])) {
            return '';
        }

        return $this->data['pubDate'];
    }

    public function setExternalUrl($externalUrl)
    {
        $this->data['externalUrl'] = $externalUrl;
        return $this;
    }

    public function getExternalUrl()
    {
        if (!isset($this->data['externalUrl'])) {
            return '';
        }

        return $this->data['externalUrl'];
    }

    public function setCopyright($copyright)
    {
        $this->data['copyright'] = $copyright;
        return $this;
    }

    public function getCopyright()
    {
        if (!isset($this->data['copyright'])) {
            return '';
        }

        return $this->data['copyright'];
    }

    public function addImage($img)
    {
        $this->data['images'][] = $img;

        return $this;
    }

    public function getImages()
    {
        if (!isset($this->data['images'])) {
            return '';
        }

        return $this->data['images'];
    }

    public function setIsPrint($value)
    {
        $this->data['isPrint'] = $value;

        return $this;
    }

    public function getIsPrint()
    {
        if (!isset($this->data['isPrint'])) {
            return '';
        }

        return $this->data['isPrint'] ? 1 : '';
    }

    public function setIsHomepage($value)
    {
        $this->data['isHomepage'] = $value;

        return $this;
    }

    public function getIsHomepage()
    {
        if (!isset($this->data['isHomepage'])) {
            return 0;
        }

        return $this->data['isHomepage'] ? 1 : 0;
    }

    public function setFirstTitle($value)
    {
        $this->data['firstTitle'] = $value;

        return $this;
    }

    public function getFirstTitle()
    {
        if (!isset($this->data['firstTitle'])) {
            return '';
        }

        return $this->data['firstTitle'];
    }

    /**
     * override this function to prevent trying to load extension multiple times
     */
    protected function _loadExtensions()
    {
    }
}
