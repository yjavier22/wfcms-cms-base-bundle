<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Wf\Bundle\CmsBaseBundle\Search\QueryBuilder\BaseQueryBuilder;

class SearchController extends Controller
{
    /**
     * @Template()
     *
     */
    public function indexAction($query = '', $page = 1, Request $request)
    {
        $finder = $this->getArticleFinder();

        if (empty($query)) {
            $query = $request->query->get('query');
        }

        $terms = $request->query->all();
        $terms['query'] = $query;
        $query = isset($terms['query']) ? $terms['query'] : $query;

        $boosts = $this->container->getParameter('wf_cms.search.boost_newest');
        //$results = $finder->findPaginatedByQueryStringBoostedByPublishDate($query, $boosts);
        $results = $finder->findPaginatedByTerms($terms);

        $results->setCurrentPage($page);

        $routeName = 'wf_generic_search';
        if (empty($query)) {
            $routeName = 'wf_cmsbase_search_index_page';
        }
        $routerParams = array_merge($terms, array(
                'query' => $query,
                'page' => $page
        ));

        if (!isset($terms['_type'])) {
            $terms['_type'] = BaseQueryBuilder::TYPE_DEFAULT;
        }

        $ret = array(
                    'categories' => $this->getCategories(),
                    'types' => BaseQueryBuilder::getTypes(),
                    'terms' => $terms,
                    'query' => $query,
                    'results' => $results,
                    'pagerOptions' => array(
                            'routeName' => $routeName,
                            'routeParams' => $routerParams
                     )
            );

        return $ret;
    }

    /**
     * @Template()
     *
     */
    public function tagAction($query = '', $page = 1, Request $request)
    {
        $finder = $this->getArticleFinder();

        if ($request->query->has('query')) {
            $query = $request->query->get('query');
        }
        $tagRepository = $this->container->get('wf_cms.repository.tag');
        $tag = $tagRepository->findOneBySlug($query);
        if ($tag) {//exact match
            // $query = $tag->getTitle();
            $results = $finder->findPaginatedByTagId($tag->getId());
        } else {
            $results = $finder->findPaginatedByTag($query);
        }

        $results->setCurrentPage($page);
        $routerParams = array(
                'query' => $query,
                'page' => $page
        );

        $ret = array(
                    'categories' => $this->getCategories(),
                    'query' => $query,
                    'results' => $results,
                    'pagerOptions' => array(
                        'routeName' => 'wf_tag_search',
                        'routeParams' => $routerParams
                    ),
                    'tag' => $tag
            );

        return $ret;

    }

    protected function getArticleFinder()
    {
        return $this->get('wf_cms.search.article_finder');
    }

    /**
     * @Template()
     */
    public function authorAction($query = '', $page = 1, Request $request)
    {
        $finder = $this->getArticleFinder();

        if ($request->query->has('query')) {
            $query = $request->query->get('query');
        }
        $query = urldecode($query);
        $userRepository = $this->container->get('wf_cms.repository.user');
        $user = $userRepository->findOneBySlug($query);

        if ($user) {
            $results = $finder->findPaginatedByAuthorId($user->getId());
        } else {
            $results = $finder->findPaginatedByAuthor($query);
        }
        $results->setCurrentPage($page);
        $routerParams = array(
                'query' => $query,
                'page' => $page
        );

        $ret = array(
                    'query' => $user ? $user->getName() : $query,
                    'categories' => $this->getCategories(),
                    'results' => $results,
                    'pagerOptions' => array(
                            'routeName' => 'wf_author_search',
                            'routeParams' => $routerParams
                     )
            );

        return $ret;
    }

    /**
     * returns a list of allowed search terms
     * @return array
     */
    protected function getAllowedTerms()
    {
        return array(
            'query',
            'publishedAt',
            'category',
            'tags',
            'author',
        );
    }

    protected function getCategories()
    {
        $categoryRepository = $this->get('wf_cms.repository.category');

        $categories = $categoryRepository->getBaseTree();

        return $categories;
    }
}
