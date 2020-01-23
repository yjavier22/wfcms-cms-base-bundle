<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * TreeRepository
 */
class TreeRepository extends NestedTreeRepository
{
    public function move($item, $target, $position)
    {
        // look for target in previous siblings
        $prev = $this->getPrevSiblings($item);
        $prev =  array_reverse($prev);
        $count = 0;
        foreach ($prev as $sibling) {
            $count++;

            if ($sibling->getId() == $target->getId()) {
                // adjust according to item position to target
                if ($position == 'after') {
                    $count--;
                }

                $result = $this->moveUp($item, $count);
                // var_dump('moved up by: ' . $count);

                return $result;
            }
        }

        // look for target in next siblings
        $next = $this->getNextSiblings($item);
        $count = 0;
        foreach ($next as $sibling) {
            $count++;
            if ($sibling->getId() == $target->getId()) {
                // adjust according to item position to target
                if ($position == 'before') {
                    $count--;
                }

                $result = $this->moveDown($item, $count);
                // var_dump('moved down by: ' . $count);

                return $result;
            }
        }
    }


    public function setParent($item, $parent)
    {
        $this->persistAsFirstChild($item);

        $em = $this->getEntityManager();
        $item->setParent($parent);
        $em->persist($item);
        $em->flush();

        return $item;
    }
}