<?php

namespace Wf\Bundle\CmsBaseBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Wf\Bundle\CmsBaseBundle\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WfCmsBaseLoadMenu
    extends AbstractFixture
    implements FixtureInterface, OrderedFixtureInterface, ContainerAwareInterface
{
    protected $menuClass;
    protected $container;
    protected $em;

    public function load(ObjectManager $em)
    {
        if ($this->hasReference('wfcms-menu-loaded')) {
            return;
        }

        $this->em = $em;

        $menuFixtures = $this->container->get('wf_cms.cms_configuration.menu_fixtures')->getMenuFixtures();
        foreach ($menuFixtures as $menuName=>$menu) {
            $parent = $this->getMenuItem($menuName);
            $this->addMenuItems($menu, $parent);
        }

        $this->addReference('wfcms-menu-loaded', new \stdClass());

        $this->em->flush();
    }

    protected function addMenuItems($itemsData, $parent)
    {
        foreach ($itemsData as $itemData) {
            $item = $this->getMenuItem($itemData['title']);
            $item->setParent($parent);
            $item->setType($itemData['type']);
            $item->setDetails($itemData['details']);

            if (isset($itemData['children'])) {
                $this->addMenuItems($itemData['children'], $item);
            }
        }
    }

    protected function getMenuItem($title)
    {
        $item = new $this->menuClass();
        $item->setTitle($title);

        $this->em->persist($item);

        return $item;
    }

    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
        $this->menuClass = $this->container->getParameter('wf_cms.entity.menu.class');
    }

    public function getOrder()
    {
        return 20;
    }
}
