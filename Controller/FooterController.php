<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 */
class FooterController extends Controller
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

                    try {
                        return $this->forward('WfCmsBaseBundle:Footer:' . $parentCategory->getSlug());
                    } catch (Exception $e) {}
                }

                return $this->forward('WfCmsBaseBundle:Footer:category', array('categorySlug' => $category->getSlug()));
            }
        }

        return array();
    }

    /**
     * @Template()
     */
    public function categoryAction($categorySlug)
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

    protected function getCategoryBySlug($categorySlug)
    {
        $categoryRepository = $this->get('wf_cms.repository.category');

        return $categoryRepository->findOneBySlug($categorySlug);
    }
}
