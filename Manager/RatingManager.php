<?php

namespace Wf\Bundle\CmsBaseBundle\Manager;

/**
 * manages ratings
 */
class RatingManager
{
    var $redis;
    var $namespace = 'rating';

    public function __construct($redis) {
        $this->redis = $redis;
    }

    public function addVote($id, $vote)
    {
        $hashName = $this->createHashName($id);

        $this->redis->hincrby($hashName, 'votes.' . $vote, 1);
        $this->redis->hincrby($hashName, 'totalVotes', 1);
        $this->redis->hincrby($hashName, 'totalRating', $vote);
    }

    public function getRating($id)
    {
        $values = $this->getValues($id);
        if (empty($values)) {
            return 0;
        }

        $totalVotes = $values['totalVotes'];
        $totalRating = $values['totalRating'];

        return $totalRating/$totalVotes;
    }

    public function getVotesCount($id)
    {
        $values = $this->getValues($id);

        return isset($values['totalVotes']) ? $values['totalVotes'] : 0;
    }

    public function getVoteValues($id)
    {
        $values = $this->getValues($id);
        unset($values['totalVotes']);
        unset($values['totalRating']);

        $votes = array();
        foreach ($values as $key => $value) {
            $parts = explode('.',$key);
            $votes[$parts[1]] = $value;
        }

        return $votes;
    }

    public function getValues($id)
    {
        $hashName = $this->createHashName($id);
        $values = $this->redis->hgetall($hashName);

        return $values;
    }

    protected function createHashName($id)
    {
        $hashName = $this->namespace . ':' . $id;

        return $hashName;
    }
}