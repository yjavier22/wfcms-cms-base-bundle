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

abstract class WfCmsBaseLoadCategories
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

    abstract public function getCategoriesData();

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->categoryClass = $this->container->getParameter('wf_cms.entity.category.class');
    }

    function load(ObjectManager $em)
    {
        if ($this->hasReference('wfcms-categories-loaded')) {
            return;
        }

        $this->em = $em;

        $count = 0;
        foreach ($this->getCategoriesData() as $categoryData) {
            $category = $this->addCategory($categoryData);
            $this->setupCategory($category, $categoryData);

            $this->em->persist($category);

            if (isset($categoryData['children'])) {
                foreach ($categoryData['children'] as $subcategoryData) {
                    $subcategory = $this->addCategory($subcategoryData, $category);
                    $this->setupCategory($subcategory, $subcategoryData, $category);

                    $this->em->persist($subcategory);
                }
            }
        }

        $this->em->flush();
    }

    protected function addCategory($categoryData, $parent = null)
    {
        if (!isset($categoryData['description'])) {
            $categoryData['description'] = $categoryData['title'];
        }

        $category = new $this->categoryClass();
        $category->setTitle($categoryData['title']);
        $category->setDescription($categoryData['description']);
        $category->setParent($parent);

        if (!empty($categoryData['template'])) {
            $category->setTemplate($categoryData['template']);
        }
        if (!empty($categoryData['articleTemplate'])) {
            $category->setArticleTemplate($categoryData['articleTemplate']);
        }
        if (!empty($categoryData['type'])) {
            $category->setType($categoryData['type']);
        }

        return $category;
    }

    protected function setupCategory(&$category, $categoryData, $parent = null)
    {
    }

    function getOrder()
    {
        return 5;
    }
}
