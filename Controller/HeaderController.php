<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\Common\Inflector\Inflector;

/**
 */
class HeaderController extends Controller
{
    /**
     * @Template()
     */
    public function defaultAction($categorySlug = '')
    {
        if (!empty($categorySlug)) {
            $categoryAction = $categorySlug . 'Action';

            if (method_exists($this, $categoryAction)) {
                return $this->forward('WfCmsBaseBundle:Header:' . $categorySlug);
            }

            $category = $this->getCategoryBySlug($categorySlug);

            if (!empty($category)) {
                $parentCategory = $category->getParent();
                if (!empty($parentCategory)) {
                    $parentCategoryAction = $parentCategory->getSlug() . 'Action';
                    if (method_exists($this, $parentCategoryAction)) {
                        return $this->forward('WfCmsBaseBundle:Header:' . $parentCategory->getSlug());
                    }
                }

                return $this->forward('WfCmsBaseBundle:Header:category', array('categorySlug' => $category->getSlug()));
            }
        }

        return array();
    }

    /**
     * @Template()
     */
    public function categoryAction($categorySlug)
    {
        $categorySlug = trim($categorySlug, "/");
        $categoryAction = lcfirst(Inflector::classify(str_replace('/', '_', $categorySlug)));
        if (method_exists($this, $categoryAction . 'Action')) {
            return $this->forward('WfCmsBaseBundle:Header:' . $categoryAction);
        }

        return $this->categoryShow($categorySlug);
    }

    public function categoryShow($categorySlug)
    {
        $category = $this->getCategoryBySlug($categorySlug);
        if (empty($category)) {
            throw new HttpException(404);
        }

        $mainCategory = $category->getParent();
        if (empty($mainCategory)) {
            $mainCategory = $category;
        }
        
        return array(
            'category' => $category,
            'mainCategory' => $mainCategory,
        );
    }

    /**
     * @Template()
     */
    public function searchAction($searchTitle)
    {
        return array(
            'searchTitle' => $searchTitle
        );
    }

    protected function getCategoryBySlug($categorySlug)
    {
        $categoryRepository = $this->get('wf_cms.repository.category');

        return $categoryRepository->findOneBySlug($categorySlug);
    }
}
