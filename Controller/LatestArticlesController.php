<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class LatestArticlesController extends Controller
{
    public $moreNewsCategoryCount = 8;

    public function getHomepageBoards()
    {
        return array();
    }

    public function getMoreNewsWidgetCategories()
    {
        return $this->get('wf_cms.repository.category')->findAll();
    }

    /**
     * @Template()
     */
    public function moreNewsAction()
    {
        // homepage boards
        $boardSlugs = $this->getHomepageBoards();

        // get ids of all pages embedded in boards
        $excludeIds = array();
        $boards = $this->get('wf_cms.repository.page')->findBySlug($boardSlugs);
        foreach ($boards as $board) {
            if (method_exists($board, 'getEmbeddedPages')) {
                $embedded = $board->getEmbeddedPages();
                $excludeIds = array_merge($excludeIds, $embedded);
            }
        }

        $articleRepository = $this->get('wf_cms.repository.page_article');

        // get categories to display
        $categories = $this->getMoreNewsWidgetCategories();

        // limit categories to 8
        $categories = array_slice($categories, 0, $this->moreNewsCategoryCount);

        foreach ($categories as $key => $category) {
            $articles = $articleRepository
                ->getLatestQB($category, 4, true, false)
                ->excludeIds($excludeIds)
                ->getQuery()
                ->getResult();

            $categories[$key]->articles = array();
            $categories[$key]->highlighted = array();
            $latestArticles = array();
            foreach ($articles as $article) {
                if (!$categories[$key]->highlighted && $article->getMainImage()) {
                    $categories[$key]->highlighted = $article;
                } else {
                    $latestArticles[] = $article;
                }
            }
            $categories[$key]->articles = array_slice($latestArticles, 0, 3);

            if (!$categories[$key]->highlighted) {
                $categories[$key]->highlighted = $articleRepository
                    ->getMediaPages('image', 1, $excludeIds, $category);
            }

        }

        return array(
            'categories'=> $categories
        );
    }

    /**
     * @Template()
     */
    public function subcategoryAction($categorySlug)
    {
        $repository = $this->get('wf_cms.repository.category');

        $category = $repository->findOneBySlug($categorySlug);
        $subcategories = $repository->getChildren($category, true);

        return array(
            'category' => $category,
            'subcategories' => $subcategories
        );
    }


    /**
     * @Template()
     */
    public function subcategoryArticlesAction($subcategorySlug)
    {
        $category = $this->get('wf_cms.repository.category')->findOneBySlug($subcategorySlug);

        $articleRepository = $this->get('wf_cms.repository.page_article');
        $all = $articleRepository
            ->getLatestQB($category, 4, true, false)
            ->getQuery()
            ->getResult();

        $highlight = array();
        $articles = array();
        foreach ($all as $article) {

            if(empty($highlight) && $article->getMainImage()) {
                $highlight = $article;
            } else {
                $articles[] = $article;
            }
        }

        if (!$highlight) {
            $highlight = $articleRepository->getMediaPages('image', 1, array(), $category);
        }

        return array(
            'category' => $category,
            'highlight' => $highlight,
            'articles' => $articles
        );
    }


}
