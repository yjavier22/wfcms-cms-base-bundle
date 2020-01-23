<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Doctrine\Common\Util\Inflector;
use Symfony\Component\HttpFoundation\Response;
use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Controller used for sidebars/metadata/etc.
 */
class CategoryBoardController extends Controller
{
    /**
     */
    public function defaultAction($boardType, $categorySlug = '')
    {
        if (!empty($categorySlug)) {
            $categoryAction = $categorySlug . 'Action';

            $forwardParams = array(
                'boardType' => $boardType
            );
            if (method_exists($this, $categoryAction)) {
                return $this->forward('WfCmsBaseBundle:CategoryBoard:' . $categorySlug, $forwardParams);
            }

            $category = $this->getCategoryBySlug($categorySlug);

            if (!empty($category)) {
                $parentCategory = $category->getParent();
                if (!empty($parentCategory)) {
                    $parentCategoryAction = $parentCategory->getSlug() . 'Action';

                    if (method_exists($this, $parentCategoryAction)) {
                        return $this->forward('WfCmsBaseBundle:CategoryBoard:' . $parentCategory->getSlug(), $forwardParams);
                    }

                }

                return $this->forward('WfCmsBaseBundle:CategoryBoard:category', array(
                    'boardType' => $boardType,
                    'categorySlug' => $category->getSlug())
                );
            }
        }

        $viewName = sprintf('WfCmsBaseBundle:%s:default.html.twig', $this->getBoardTypeTemplateDir($boardType));
        return $this->render($viewName);
    }

    /**
     */
    public function categoryAction($boardType, $categorySlug)
    {
        $category = $this->getCategoryBySlug($categorySlug);

        if (empty($category)) {
            throw $this->createNotFoundException('Category not found!');
        }

        $board = $this->getCategoryBoard($boardType, $category);
        if (empty($board)) {
            //try to get its parent's board
            $mainCategory = $category->getParent();
            if (!empty($mainCategory)) {
                $board = $this->getCategoryBoard($boardType, $mainCategory);
            }
        }

        if (empty($board)) {
            return $this->forward('WfCmsBaseBundle:CategoryBoard:default', array('boardType' => $boardType));
        }

        $viewName = sprintf('WfCmsBaseBundle:%s:category.html.twig', $this->getBoardTypeTemplateDir($boardType));
        return $this->render($viewName, array('board' => $board));
    }

    protected function getBoardTypeTemplateDir($boardType)
    {
        return Inflector::classify($boardType);
    }

    protected function getCategoryBySlug($categorySlug)
    {
        $categoryRepository = $this->get('wf_cms.repository.category');

        return $categoryRepository->findOneBySlug($categorySlug);
    }

    protected function getCategoryBoard($boardType, $category)
    {
        $categoryRepository = $this->get('wf_cms.repository.page');

        $boardQB = $categoryRepository->getBaseQB();
        $boardQB->byType($boardType);
        $boardQB->byCategory($category, null, $categoryFields = false);

        if ($boardType == 'sidebar') {
            //some projects also have an article sidebar
            $boardQB->bySlug(sprintf('%s/category-sidebar', $category->getSlug()));
        }

        $board = $boardQB->getSingleResult();

        if (is_null($board)) {
            return null;
        }

        $pageManager = $this->get('wf_cms.page_manager');

        return $pageManager->isPublished($board);
    }

}
