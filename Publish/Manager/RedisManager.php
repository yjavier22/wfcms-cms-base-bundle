<?php

namespace Wf\Bundle\CmsBaseBundle\Publish\Manager;

/**
 * @author cv
 * Test: phpunit -v -c app/admin vendor/wfcms/cms-base-bundle/Wf/Bundle/CmsBaseBundle/Tests/Publish/Manager/RedisManagerTest.php
 */
class RedisManager extends BaseManager
{
    protected $redis;
    
    protected $prefix = '';

    public function __construct($redis, $prefix = '')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }
    
    protected function _get($listName, $offset = 0, $length = null)
    {
        if (is_null($length)) {
            $end = -1;
        } else {
            $end = $offset + $length;
        }

        $response = $this->redis->zrevrange($this->prefix . $listName, $offset, $end, 'WITHSCORES');
        $list = array();
        foreach ($response as $listInfo) {
            list($id, $key) = $listInfo;
            $list[] = intval($id);
        }

        return $list;
    }
    
    protected function _getByScore($listName, $offset = 0, $length = null, $interval = array())
    {
        if (is_null($length)) {
            $end = -1;
        } else {
            $end = $offset + $length;
        }
        
        if (empty($interval)) {
            $interval = array();
        }
        if (empty($interval['until'])) {
            $interval['until'] = time();
        }
        if (empty($interval['from'])) {
            $interval['from'] = 0;
        }
        
        $response = $this->redis->zrevrangebyscore($this->prefix . $listName, $interval['until'], $interval['from'], 'WITHSCORES');
        $list = array();
        foreach ($response as $listInfo) {
            list($id, $key) = $listInfo;
            $list[] = intval($id);
        }

        return $list;
    }

    protected function _add($listName, $id, $key)
    {
        $this->redis->zadd($this->prefix . $listName, array($id => $key));
    }

    protected function _remove($listName, $id)
    {
        return $this->redis->zrem($this->prefix . $listName, $id);
    }

    protected function _getLists($key)
    {
        return $this->redis->hget($this->prefix . static::CURRENT_LIST_SET_NAME, $key);
    }

    protected function _setLists($key, $value)
    {
        return $this->redis->hset($this->prefix . static::CURRENT_LIST_SET_NAME, $key, $value);
    }

    protected function _searchLists($mask)
    {
        return $this->redis->keys($this->prefix . $mask);
    }

    protected function _removeLists($mask)
    {
        $lists = $this->_searchLists($this->prefix . $mask);
        foreach($lists as $listName) {
            $this->redis->del($this->prefix . $listName);
        }
    }

    protected function _count($listName)
    {
        return $this->redis->zcount($this->prefix . $listName, '-inf', '+inf');
    }

    protected function _index($listName, $id)
    {
        return $this->redis->zrevrank($listName, $id);
    }

    protected function _getSet($setName)
    {
        return $this->redis->hgetall($this->prefix . $setName);
    }

    protected function _removeKeyInSet($listName, $keys)
    {
        $this->redis->hdel($this->prefix . $listName, $keys);
    }
    
    protected function _trim($listName, $size)
    {
        $no = $this->_count($listName);
        if ($no <= $size) {
            return 0;
        }

        return $this->redis->zremrangebyrank($this->prefix . $listName, 0, -1 * ($no - ($size + 1)));
    }

    protected function _countCurrent()
    {
        return $this->redis->hlen($this->prefix . static::CURRENT_LIST_SET_NAME);
    }

    protected function _first()
    {
        $val = $this->redis->zrange($this->prefix . static::LATEST, 0, 0, 'WITHSCORES');
        if (empty($val)) {
            return null;
        }

        return $val[0];
    }

}
