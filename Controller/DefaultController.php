<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    /**
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

     /**
     * @Template()
     */
    public function homepageArticlesAction()
    {
        $articleIds = array();
        $articles = array();

        $pageRepository = $this->get('wf_cms.repository.page');

        $homeBoards = $pageRepository->findByPageType('homepage');
        foreach ($homeBoards as $board) {
            if (method_exists($board, 'getEmbeddedPages')) {
                $articleIds = array_merge($articleIds, $board->getEmbeddedPages());
            }
        }

        if ($articleIds) {
            $unorderedArticles = $pageRepository->findById($articleIds);

            foreach ($unorderedArticles as $article) {
                $key = array_search($article->getId(), $articleIds);
                $articles[$key] = $article;
            }
            ksort($articles);
        }

        return array(
            'articles' => $articles,
        );
    }
}
