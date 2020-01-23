<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Exception\NotValidCurrentPageException;

/**
 * opinion section pages
 *
 * @author cv
 */
class OpinionController extends Controller
{
    const EDITORIAL_CATEGORY = 'opinion/editorial';
    const OPINION_CATEGORY = 'opinion';

    /** Editorial */
    /**
     * get the last editorial
     * @return \Wf\Bundle\CmsBaseBundle\Entity\Page
     */
    protected function getEditorial()
    {
        $pageRepository = $this->get('wf_cms.repository.page_article');
        $categoryRepository = $this->get('wf_cms.repository.category');
        $category = $categoryRepository->findOneBySlug(static::EDITORIAL_CATEGORY);
        $editorial = $pageRepository->getLastByCategory($category);

        return $editorial;
    }

    /**
     * shows the last editorial in a box
     * @Template()
     */
    public function lastEditorialAction()
    {
        return array(
            'editorial' => $this->getEditorial(),
        );
    }

    /**
     * @Template()
     */
    public function editorialAction($page = 1)
    {
        $pageRepository = $this->get('wf_cms.repository.page_article');
        $categoryRepository = $this->get('wf_cms.repository.category');
        $category = $categoryRepository->findOneBySlug(static::EDITORIAL_CATEGORY);
        $qb = $pageRepository->getLatestQB($category);

        $pagerFactory = $this->get('wf_cms.pager.factory');
        $pages = $pagerFactory->createPager($qb, $page);

        return array(
            'pages' => $pages,
        );
    }

    /**
     * shows the last opinions in a box
     * @return array
     */
    protected function getLatestColumns()
    {
        $request = $this->getRequest();
        $limit = $request->get('limit', 50);
        $pageRepository = $this->get('wf_cms.repository.page_article');
        $categoryRepository = $this->get('wf_cms.repository.category');
        $category = $categoryRepository->findOneBySlug(static::OPINION_CATEGORY);
        $columns = $pageRepository->getLatestPublished(null, $category, $limit, false);

        if (empty($columns)) {
            return array();
        }

        return $columns;
    }

    /** Opinions */
    /**
     * latest items in the opinion category
     * @Template()
     */
    public function latestColumnsAction()
    {
        return array(
            'columns' => $this->getLatestColumns(),
        );
    }

    /**
     * get a list of all columnists
     * @return array
     */
    protected function getColumnists()
    {
        $qb = $this->get('wf_cms.repository.user')->createQueryBuilder('u')
            ->where('u.columnist = true')
            ->orderBy('u.lastName');

        $authors = $qb->getQuery()->getResult();
        if (empty($authors)) {
            return array();
        }

        return $authors;
    }

    /**
     * list all columnists
     * @Template()
     */
    public function columnistsAction()
    {
        return array(
            'columnists' => $this->getColumnists(),
        );
    }

    /**
     * get the requested columnist
     * @param string $authorSlug
     * @return \Wf\Bundle\CmsBaseBundle\Entity\User
     */
    protected function getColumnist($authorSlug)
    {
        $userManager = $this->get('wf_cms.repository.user');
        return $userManager->findOneBySlug($authorSlug);
    }

    /**
     * get the opinions of a columnist
     * @param \Wf\Bundle\CmsBaseBundle\Entity\User $author
     * @param integer $page
     * @throws \InvalidArgumentException when the page is out of range
     */
    protected function getColumnistColumns($author, $page)
    {
        $pageRepository = $this->get('wf_cms.repository.page_article');
        $categoryRepository = $this->get('wf_cms.repository.category');
        $category = $categoryRepository->findOneBySlug(static::OPINION_CATEGORY);

        $qb = $pageRepository->getByAuthorQB($author, $category);

        $pagerFactory = $this->get('wf_cms.pager.factory');
        try {
            $columns = $pagerFactory->createPager($qb, $page);
        } catch (NotValidCurrentPageException $e) {
            throw new \InvalidArgumentException($e->getMessage(), $e);
        }

        return $columns;
    }

    /**
     * displays the opinions of a columnist
     * @Template()
     */
    public function columnAction($authorSlug, $page)
    {
        $columnist = $this->getColumnist($authorSlug);
        if (empty($columnist)) {
            throw $this->createNotFoundException();
        }

        try {
            $columns = $this->getColumnistColumns($columnist, $page);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException('Not found', $e);
        }

        return array(
            'columnist' => $columnist,
            'columns' => $columns,
        );
    }

}