<?php

namespace Wf\Bundle\CmsBaseBundle\Manager;

use Wf\Bundle\CmsBaseBundle\Entity\Repository\CategoryRepository;
use Wf\Bundle\CmsBaseAdminBundle\Configuration\BoardConfiguration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Wf\Bundle\CmsBaseBundle\Entity\PageRepository;

class BoardManager
{
    protected $menuBoards = array();

    /**
     * This service is (also) used in doctrine listeners,
     * we can't inject the repositories directly, as this would create
     * a circular dependency
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        if (is_null($this->entityManager)) {
            $this->entityManager = $this->container->get('doctrine')->getManager();
        }

        return $this->entityManager;
    }

    /**
     * @return PageRepository
     */
    public function getPageRepository()
    {
        if (is_null($this->pageRepository)) {
            $this->pageRepository = $this->container->get('wf_cms.repository.page');
        }

        return $this->pageRepository;
    }

    /**
     * @return CategoryRepository
     */
    public function getCategoryRepository()
    {
        if (is_null($this->categoryRepository)) {
            $this->categoryRepository = $this->container->get('wf_cms.repository.category');
        }

        return $this->categoryRepository;
    }

    public function getBoardTypes()
    {
        return $this->getPageRepository()->getBoardTypes();
    }

    public function getBoardsByType($type)
    {
        return $this->getPageRepository()->findByPageType($type);
    }

    /**
     * @return BoardConfiguration
     */
    public function getBoardConfiguration()
    {
        return $this->container->get('wf_cms.cms_configuration.board');
    }

    /**
     * Delete all old boards and inserts them all again
     */
    public function reloadAllBoards()
    {
        $this->deleteAllBoards();
        $this->addAllBoards();
    }

    public function reloadBySlug($slug)
    {
        $boardDefinition = $this->getBoardConfiguration()->getBySlug($slug);
        return $this->reloadByDefinition($boardDefinition);
    }

    public function reloadByDefinition($boardDefinition = null)
    {
        if (empty($boardDefinition) || empty($boardDefinition['slug'])) {
            throw new \InvalidArgumentException('No board definition');
        }

        $em = $this->getEntityManager();

        $boardSlug = $boardDefinition['slug'];
        if (!empty($boardDefinition['category'])) {
            $categoryRepository  = $this->getCategoryRepository();
            $category = $categoryRepository->findOneByTitle($boardDefinition['category']);
            if ($category) {
                $boardSlug = $category->getSlug() . '/' . $boardSlug;
            }
        }

        $board = $this->getPageRepository()->findOneBySlug($boardSlug);
        if (!empty($board)) {
            $em->remove($board);
            $em->flush();
        }

        $this->addBoard($boardDefinition);
        $em->flush();
    }

    public function reloadByCategory($category)
    {
        $this->deleteByCategory($category);
        $this->addByCategory($category);
    }

    protected function deleteByCategory($category)
    {
        $boardsDefinitions = $this->getBoardConfiguration()->getBoardsByCategory($category);
        $pageRepository = $this->getPageRepository();
        $categoryRepository = $this->getCategoryRepository();
        $em = $this->getEntityManager();

        foreach ($boardsDefinitions as $boardDefinition) {
            $boardSlug = $boardDefinition['slug'];
            if (!empty($boardDefinition['category'])) {
                $category = $categoryRepository->findOneByTitle($boardDefinition['category']);
                $boardSlug = $category->getSlug() . '/' . $boardSlug;
            }
            $board = $pageRepository->findOneBySlug($boardSlug);
            if ($board) {
                $em->remove($board);
            }
        }

        $em->flush();
    }

    protected function addByCategory($category)
    {
        $boardsDefinitions = $this->getBoardConfiguration()->getBoardsByCategory($category);

        foreach ($boardsDefinitions as $boardDefinition) {
            $this->addBoard($boardDefinition);
        }

        $this->getEntityManager()->flush();
    }

    public function deleteAllBoards()
    {
        $em = $this->getEntityManager();

        foreach ($this->getBoardTypes() as $type) {
            $boards = $this->getPageRepository()->findByPageType($type);
            foreach ($boards as $board) {
                $em->remove($board);
            }
        }

        $em->flush();
    }

    public function addAllBoards()
    {
        $em = $this->getEntityManager();

        $categories = $this->getBoardConfiguration()->getBoardCategories();

        foreach ($categories as $category) {
            $this->addByCategory($category);
        }

        $em->flush();
    }

    /**
     * Adds a board and returns it. It only calls $em->persist, not flush, this should be called from outside this method,
     * after all boards have been persisted
     * @param array $definition with the following keys:
     * 		- title
     * 		- slug
     * 		- template (default 'default')
     * 		- position (default 0)
     * 		- type (default Page:TYPE_BOARD)
     */
    protected function addBoard($definition)
    {
        if (!isset($definition['title'])) {
            throw new \InvalidArgumentException('Error adding a board - the title must be spefied in the board definition');
        }

        if (!isset($definition['slug'])) {
            throw new \InvalidArgumentException('Error adding a board - the slug must be spefied in the board definition');
        }

        if (!isset($definition['type'])) {
            $definition['type'] = Page::TYPE_BOARD;
        }

        if (!isset($definition['template'])) {
            $definition['template'] = 'default';
        }

        if (!isset($definition['position'])) {
            $definition['position'] = 0;
        }

        $class = $this->container->getParameter('wf_cms.entity.page_' . $definition['type'] . '.class');

        $pageManager = $this->container->get('wf_cms.page_manager');
        $page = $pageManager->getNewPage($definition['type']);
        $page->setTitle($definition['title']);
        $page->setTemplate($definition['template']);
        $page->setSlug($definition['slug']);
        $page->setPosition($definition['position']);

        if (isset($definition['category'])) {
            $category = $this->getCategoryRepository()->findOneByTitle($definition['category']);
            if (empty($category)) {
                throw new \InvalidArgumentException(sprintf('Category %s for board %s (%s) not found', $definition['category'], $definition['title'], $definition['slug']));
            }
            $page->setCategory($category);
        }

        $this->getEntityManager()->persist($page);

        return $page;
    }

}