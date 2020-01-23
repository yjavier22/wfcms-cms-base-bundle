<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * PollOptionRepository
 */
class PollOptionRepository extends EntityRepository
{
    public function increaseOptionVoteCount($poll, $optionName)
    {
        $option = $this->getPollOption($poll, $optionName);

        if (!$option) {
            return false;
        }

        $votes = $option->getVoteCount();
        $option->setVoteCount(++$votes);

        $em = $this->getEntityManager();
        $em->persist($option);
        $em->flush();

        return $option->getVoteCount();
    }

    public function getPollOption($poll, $optionName)
    {
        $qb = $this->createQueryBuilder('o');

        $options = $qb->select('o')
            ->where('o.optionName = :optionName')
            ->andWhere('o.poll = :poll')
            ->setParameter('optionName', $optionName)
            ->setParameter('poll', $poll)
            ->getQuery()
            ->getResult();

        if (isset($options[0])) {
            return $options[0];
        }

        return null;
    }


}