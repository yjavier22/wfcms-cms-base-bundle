<?php

namespace Wf\Bundle\CmsBaseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 */
class PollOption
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Poll",
     *      inversedBy="options")
     */
    private $poll;

    /**
     * @ORM\Column(name="option_name", type="string", nullable=true)
     */
    private $optionName;

    /**
     * @ORM\Column(name="vote_count", type="integer", nullable=true)
     */
    private $voteCount = 0;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setPoll($poll)
    {
        $this->poll = $poll;
        return $this;
    }

    public function getPoll()
    {
        return $this->poll;
    }

    public function setVoteCount($voteCount)
    {
        $this->voteCount = $voteCount;
        return $this;
    }

    public function getVoteCount()
    {
        return $this->voteCount;
    }

    /**
     * Set optionName
     *
     * @param string $optionName
     * @return PollOption
     */
    public function setOptionName($optionName)
    {
        $this->optionName = $optionName;

        return $this;
    }

    /**
     * Get optionName
     *
     * @return string
     */
    public function getOptionName()
    {
        return $this->optionName;
    }

    public function __toString()
    {
        if ($this->optionName) {
            return $this->optionName;
        }

        return '';
    }
}
