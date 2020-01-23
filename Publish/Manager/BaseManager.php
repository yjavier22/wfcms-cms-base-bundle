<?php

namespace Wf\Bundle\CmsBaseBundle\Publish\Manager;

use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Wf\Bundle\CmsBaseBundle\Entity\Category;

/**
 * @author cv
 * Test: phpunit -v -c app/admin vendor/wfcms/cms-base-bundle/Wf/Bundle/CmsBaseBundle/Tests/Publish/Manager/RedisManagerTest.php
 */
abstract class BaseManager
{
    const LATEST = 'latest';
    const LATEST_CATEGORY = 'latest:category:%d:full';
    const LATEST_MAIN_CATEGORY = 'latest:category:%d';

    const LATEST_AUTHOR = 'latest:author:%d';
    const LATEST_AUTHOR_CATEGORY = 'latest:author:%d:%d';

    const LATEST_MEDIA_IMAGES = 'latest:images';
    const LATEST_MEDIA_IMAGES_CATEGORY = 'latest:images:%d';
    const LATEST_MEDIA_VIDEOS = 'latest:videos';
    const LATEST_MEDIA_VIDEOS_CATEGORY = 'latest:videos:%d';
    const LATEST_MEDIA_AUDIOS = 'latest:audios';
    const LATEST_MEDIA_AUDIOS_CATEGORY = 'latest:audios:%d';

    const LATEST_TAGS = 'latest:tags:%s';
    const LATEST_TAGS_TYPE = 'latest:tags:%s:%s';

    const LATEST_DATE = 'latest:date:%s';
    const LATEST_DATE_CATEGORY = 'latest:date:%s:%d';

    const LATEST_TEMPLATE = 'latest:template:%s';

    const CURRENT_LIST_KEY_TEMPLATE = 'lists:%d';
    const CURRENT_LIST_SET_NAME = 'publish:current_lists';

    protected $size;

    protected $categoryPath;

    protected $pageClass;

    protected $listGroups = array('category', 'image', 'date', 'author');

    public function setListMaxSize($maxSize)
    {
        $this->size = $maxSize;
    }

    public function getListMaxSize()
    {
        return $this->size;
    }

    /**
     * get all lists for a page
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Page $page
     * @return array
     */
    public function getPageLists(Page $page)
    {
        $lists = array();

        $this->getCategoryLists($page, $lists);
        $this->getImageLists($page, $lists);
        $this->getDateLists($page, $lists);
        $this->getAuthorLists($page, $lists);
        $this->getTagLists($page, $lists);

        return $lists;
    }

    public function getFirstDate()
    {
        $firstItem = $this->_first();
        $date = new \DateTime();
        if (isset($firstItem[1])) {
            $date->setTimestamp($firstItem[1]);
        }
        return $date;
    }

    protected function getCategoryLists(Page $page, &$lists)
    {
        $category = $page->getCategory();
        $lists[] = sprintf(self::LATEST_MAIN_CATEGORY, $category->getId());

        $categories = $this->getCategoryPath($page);
        $latest = false;

        foreach($categories as $category) {
            if ($category->getType() == Category::TYPE_NEWS) {
                $latest = true;
            }

            $lists[] = sprintf(static::LATEST_CATEGORY, $category->getId());
        }

        if ($latest) {
            $lists[] = static::LATEST;
        }
    }

    protected function getAuthorLists(Page $page, &$lists)
    {
        $categories = $this->getCategoryPath($page);
        if (!method_exists($page, 'getAuthors')) {
            return;
        }

        $authors = $page->getAuthors();

        if (!$authors) {
//            error_log(sprintf('[NOAUTHOR] Page with id %s has no author', $page->getId()));

            return;
        }

        foreach($authors as $author) {
            $lists[] = sprintf(static::LATEST_AUTHOR, $author->getId());
            foreach ($categories as $category) {
                $lists[] = sprintf(static::LATEST_AUTHOR_CATEGORY, $author->getId(), $category->getId());
            }
        }
    }

    protected function getImageLists(Page $page, &$lists)
    {
        if (!method_exists($page, 'hasImages')) {
            return;
        }
        if ($page->hasImages()) {
            $categories = $this->getCategoryPath($page);

            $lists[] = static::LATEST_MEDIA_IMAGES;
            foreach ($categories as $category) {
                $lists[] = sprintf(static::LATEST_MEDIA_IMAGES_CATEGORY, $category->getId());
            }
        }
    }

    protected function getVideoLists(Page $page, &$lists)
    {
        if (!method_exists($page, 'hasVideos')) {
            return;
        }
        if ($page->hasVideos()) {
            $categories = $this->getCategoryPath($page);

            $lists[] = static::LATEST_MEDIA_VIDEOS;
            foreach ($categories as $category) {
                $lists[] = sprintf(static::LATEST_MEDIA_VIDEOS_CATEGORY, $category->getId());
            }
        }
    }

    protected function getAudioLists(Page $page, &$lists)
    {
        if (!method_exists($page, 'hasAudios')) {
            return;
        }
        if ($page->hasAudios()) {
            $categories = $this->getCategoryPath($page);

            $lists[] = static::LATEST_MEDIA_AUDIOS;
            foreach ($categories as $category) {
                $lists[] = sprintf(static::LATEST_MEDIA_AUDIOS_CATEGORY, $category->getId());
            }
        }
    }

    protected function getTemplateLists(Page $page, &$lists)
    {
        $template = $page->getTemplate();

        $lists[] = sprintf(static::LATEST_TEMPLATE, $template);
    }

    protected function getTagLists(Page $page, &$lists, $type = null)
    {
        if (!method_exists($page, 'getTags')) {
            return;
        }

        $allTags = $tags = $page->getTags();
        if (!$allTags || empty($allTags)) {
            return;
        }

        $listTemplate = static::LATEST_TAGS;
        $tags = array();
        if (!empty($type)) {
            $listTemplate = static::LATEST_TAGS_TYPE;
        }

        foreach($allTags as $tag) {
            if ($tag->getType() == $type) {
                $tags[] = $tag;
            }
        }

        foreach($tags as $tag) {
            $lists[] = !empty($type)
                        ? sprintf($listTemplate, $type, $tag->getId())
                        : sprintf($listTemplate, $tag->getId());
        }
    }

    protected function getDateLists(Page $page, &$lists, $format = 'Y-m-d')
    {
        if (!$page->isPublished()) {//SEEME: unpublished pages will never get cleaned from this lists
            return;
        }
        $date = $page->getFirstPublishedAt()->format($format);
        $categories = $this->getCategoryPath($page);

        $lists[] = sprintf(static::LATEST_DATE, $date);
        foreach ($categories as $category) {
            $lists[] = sprintf(static::LATEST_DATE_CATEGORY, $date, $category->getId());
        }
    }

    protected function _getCategories(Category $category = null)
    {
        $categories = array();
        $parent = $category;
        while($parent) {
            $categories[] = $parent;
            $parent = $parent->getParent();
        }

        return $categories;
    }

    /**
     * get all parent categories including the specified category
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Category $category
     * @return array
     */
    protected function getCategoryPath(Page $page)
    {
        $categories = array();
        foreach($page->getCategories() as $category)
        {
            $categories = array_merge($categories, $this->_getCategories($category));
        }

        return array_unique($categories);
    }

    /**
     * add a page to all lists
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Page $page
     * @param string $type
     */
    public function processPage(Page $page, $type)
    {
        $lists = $this->getPageLists($page);

        if ($type == 'update') {
            $currentLists = $this->getCurrentLists($page);
            $toRemoveFromLists = array_diff($currentLists, $lists);
            foreach ($toRemoveFromLists as $listName) {
                $this->removePage($listName, $page);
            }
        }

        foreach ($lists as $listName) {
            $this->addPage($listName, $page);
        }

        $this->setCurrentLists($page, $lists);
    }

    /**
     * remove a series of pages from all lists
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Page[] $pages
     */
    public function removePages($pages)
    {
        foreach ($pages as $page) {
            $lists = $this->getPageLists($page);
            foreach ($lists as $listName) {
                $this->removePage($listName, $page);
            }
        }
    }

    /**
     * get all pages in a list
     * @param string $listName
     * @return array
     */
    public function getListPages($listName)
    {
        return $this->get($listName);
    }

    /**
     * get the index of a page in the specified list
     * @param string $listName
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Page|integer $page
     * @return type
     */
    public function getPageIndex($listName, $page)
    {
        return $this->_index($listName, is_numeric($page) ? $page : $page->getId());
    }

    public function getListSlice($listName, $offset, $length, $excluded = array())
    {
        foreach((array)$excluded as $id) {
            $index = $this->_index($listName, $id);
            if (is_null($index)) {
                continue;
            }
            if ($index <= $offset) {//[---Excluded---offset---offset+length---]
                $offset++;
            }
            if ($index > $offset && $index <= $offset + $length) {//[---offset---Excluded---offset+length---]
                $length++;
            }
        }

        $items = $this->_get($listName, $offset, $length);
        return array_diff($items, (array)$excluded);
    }

    public function getListSliceByScore($listName, $offset, $length, $interval = array())
    {
        return $this->_getByScore($listName, $offset, $length, $interval);
    }

    public function getListSize($listName, $excluded = null)
    {
        $size = $this->_count($listName);
        $size -= is_array($excluded) || $excluded instanceof \Countable ? count($excluded) : 0;

        return min($size, $this->size);
    }

    /**
     * adds a page to a list
     * @param string $listName
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Page $page
     */
    public function addPage($listName, $page)
    {
        $id = intval($page->getId());
        $key = $this->getPageScore($listName, $page);

        $this->add($listName, $id, $key);
    }
    
    public function getPageScore($listName, $page)
    {
        return intval($page->getFirstPublishedAt()->format('U'));
    }

    /**
     * remove a page from a list
     * @param string $listName
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Page $page
     */
    public function removePage($listName, $page)
    {
        $id = $page->getId();
        $this->remove($listName, $id);
    }

    /**
     * add an item on the backends' list
     * @param string $listName
     * @param integer $id
     * @param integer $key
     */
    public function add($listName, $id, $key)
    {
        $result = $this->_add($listName, $id, $key);

        $this->trim($listName);

        return $result;
    }

    /**
     * remove an item from the backends' list
     * @param string $listName
     * @param integer $id
     */
    public function remove($listName, $id)
    {
        return $this->_remove($listName, $id);
    }

    /**
     * trim the size of a list
     * @param string $listName
     * @return integer
     */
    public function trim($listName)
    {
        return $this->_trim($listName, $this->size + 100);
    }

    /**
     * get the members of a list from the backends' list
     * @param string $listName
     * @return array array('<score=publish unix ts>' => '<member=page if>')
     */
    public function get($listName)
    {
        return $this->_get($listName);
    }

    public function searchLists($mask)
    {
        return $this->_searchLists($mask);
    }

    public function removeLists($mask)
    {
        return $this->_removeLists($mask);
    }

    public function clear()
    {
        $this->removeLists('latest*');
        $this->removeLists(static::CURRENT_LIST_SET_NAME);
    }

    /**
     * get the lists that page is in
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Page $page
     * @return array
     */
    public function getCurrentLists(Page $page)
    {
        $key = sprintf(static::CURRENT_LIST_KEY_TEMPLATE, $page->getId());
        $stored = (string)$this->_getLists($key);

        return explode(',', $stored);
    }

    /**
     * set the lists that the page is in now
     * @param \Wf\Bundle\CmsBaseBundle\Entity\Page $page
     * @param array $lists
     * @return boolean
     */
    public function setCurrentLists($page, $lists)
    {
        $key = sprintf(static::CURRENT_LIST_KEY_TEMPLATE, is_object($page) ? $page->getId() : $page);
        $stored = implode(',', $lists);

        return $this->_setLists($key, $stored);
    }

    public function getCurrentStoredLists()
    {
        return $this->_getSet(static::CURRENT_LIST_SET_NAME);
    }

    public function removeCurrentLists($keys)
    {
        return $this->_removeKeyInSet(static::CURRENT_LIST_SET_NAME, $keys);
    }

    /**
     * count all the sorted lists
     * @return integer
     */
    public function countLists()
    {
        $lists = $this->searchLists('latest.*');

        return count($lists);
    }

    /**
     * count all the items in the current lists hset
     * @return integer
     */
    public function countCurrent()
    {
        return $this->_countCurrent();
    }

    /**
     * add an item on the backends' list
     * @param string $listName
     * @param integer $id
     * @param integer $key
     */
    abstract protected function _add($listName, $id, $key);

    /**
     * remove an item from the backends' list
     * @param string $listName
     * @param integer $id
     */
    abstract protected function _remove($listName, $id);

    /**
     * get the members of a list from the backends' list
     * @param string $listName
     * @param integer $offset
     * @param integer $end
     * @return array array('<score=publish unix ts>' => '<member=page if>')
     */
    abstract protected function _get($listName, $offset = 0, $length = null);

    /**
     * get the members if a list from the backends' list by score interval
     * @param string $listName
     * @param integer $offset
     * @param integer $length
     * @param array $interval array('from' => ts, 'until' => ts)
     */
    abstract protected function _getByScore($listName, $offset = 0, $length = null, $interval = array());

    /**
     * get current lists to check against new lists
     * @param string $key
     * @return array
     */
    abstract protected function _getLists($key);

    /**
     * set the current lists
     * @param string $key
     * @param string $value
     */
    abstract protected function _setLists($key, $value);

    /**
     * clear all lists specified by mask (use * as wildcard)
     * @param string $mask
     */
    abstract protected function _removeLists($mask);

    /**
     * get the number of items in the back-end's list
     * @param string $$listName
     */
    abstract protected function _count($listName);

    /**
     * get the number of items in the current lists hash
     * @return integer
     */
    abstract protected function _countCurrent();

    /**
     * get the index of a member in a sorted set
     * @param string $listName
     * @param string $id
     */
    abstract protected function _index($listName, $id);

    /**
     * search for lists based on wild card query
     * @param string $mask
     */
    abstract protected function _searchLists($mask);

    /**
     * get all values stored in hset
     * @param string $setName
     */
    abstract protected function _getSet($setName);

    /**
     * remove keys from the set that holds the current list
     * $param string $listName
     * @param array $keys
     */
    abstract protected function _removeKeyInSet($listName, $keys);

    abstract protected function _trim($listName, $size);
}
