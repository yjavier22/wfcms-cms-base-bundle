<?php

namespace Wf\Bundle\CmsBaseBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class Load extends AbstractFixture
    implements FixtureInterface, OrderedFixtureInterface, ContainerAwareInterface
{
    function load(ObjectManager $em)
    {
        $class = $this->container->getParameter('wf_cms.entity.advertisement_page.class');

        // default
        $object = new $class;
        $object->setLocked(true);
        $object->setTitle('Default');
        $object->setType('default');
        $object->setDetails(json_encode(array('type' => '/')));
        $object->setOASPage('/');
        $object->setOASPositions('Top');
        $object->setActive(true);
        $em->persist($object);

        // homepage
        $object = new $class;
        $object->setLocked(true);
        $object->setTitle('Home');
        $object->setType('home');
        $object->setDetails(json_encode(array('type' => '/')));
        $object->setOASPage('/home');
        $object->setOASPositions('Top');
        $object->setActive(true);
        $em->persist($object);

        $em->flush();
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    function getOrder()
    {
        return 25;
    }
}