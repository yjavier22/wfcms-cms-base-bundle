<?php

namespace Wf\Bundle\CmsBaseBundle\DataFixtures\ORM;

use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Wf\Bundle\CommonBundle\Util\Lipsum;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Wf\Bundle\CommonBundle\Util\ClassUtil;
use Symfony\Component\Finder\Finder;

class WfCmsBaseLoadBoard
    extends AbstractFixture
    implements FixtureInterface, OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var String
     */
    protected $pageClass;

    public function __construct()
    {
    }

    public function getCategoriesData()
    {
        $titles = 'Category1/Category2';
        $titlesArray = explode('/', $titles);

        $ret = array();
        foreach ($titlesArray as $title) {
            $ret[] = array(
                    'title' => $title,
                    'description' => $title,
                    );
        }

        //$ret = array_slice($ret, 0, 1);

        return $ret;
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->itemsPerCategory = $this->container->getParameter('wf_cms.fixtures.articles');
        $this->editionsNo = $this->container->getParameter('wf_cms.fixtures.editions');
        if (empty($this->editionsNo) || !is_numeric($this->editionsNo)) {
            $this->editionsNo = 1;
        }
    }

    function load(ObjectManager $em)
    {
        if ($this->hasReference('wfcms-board-loaded')) {
            return;
        }

        $this->em = $em;

        $this->container->get('wf_cms.board_manager')->addAllBoards();

        return;
    }

    protected function postFlush()
    {
    }

    function getOrder()
    {
        return 10;
    }
}
