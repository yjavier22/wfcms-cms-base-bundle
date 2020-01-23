<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Wf\Bundle\CmsBaseBundle\Entity\Tag;

trait TaggableTrait
{
    /**
     * to avoid importing the TaggableTrait::__construct
     * http://stackoverflow.com/questions/12478124/how-to-overload-class-constructor-within-traits-in-php-5-4
     */
    protected function __taggableConstruct()
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * Add tag
     *
     * @param Tag $tag
     */
    public function addTag(Tag $tag)
    {
        foreach ($this->tags as $currentTag) {
            if ($currentTag->equals($tag)) {
                return;
            }
        }

        $this->tags[] = $tag;
    }

    /**
     * Add tag
     *
     * @param Tag $tag
     */
    public function removeTag(Tag $removeTag)
    {
        $found = false;
        foreach ($this->tags as $k => $tag) {
            if ($tag->equals($removeTag)) {
                $found = true;
                break;
            }
        }

        if ($found) {
            unset($this->tags[$k]);
        }
    }

    /**
     * Set all tags at once
     *
     * @param array(Tag) $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    /**
     * Get tags
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    public function getTagIds()
    {
        $ret = array();

        foreach ($this->getTags() as $tag) {
            $ret[] = $tag->getId();
        }

        return $ret;
    }

    public function getTagTitles()
    {
        $ret = array();

        foreach ($this->getTags() as $tag) {
            $ret[] = $tag->getTitle();
        }

        return $ret;
    }

    /**
     * @return an array with the tag id as the key and the tag value as the value
     */
    public function getTagsHash()
    {
        $ret = array();

        foreach ($this->getTags() as $tag) {
            $ret[$tag->getId()] = $tag->getTitle();
        }

        return $ret;
    }

    /**
     * @return a zero-indexed array with ag value as the value (ElasticSearch complains if it's fed with a hash)
     */
    public function getTagsArray()
    {
        $ret = array();

        foreach ($this->getTags() as $tag) {
            $ret[] = $tag->getTitle();
        }

        return $ret;
    }


} 