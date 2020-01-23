<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends Controller
{
    /**
     */
    public function showAction($slug, Request $request)
    {
        $pageRepository = $this->get('wf_cms.repository.page');
        $pageManager = $this->get('wf_cms.page_manager');

        $page = $pageRepository->findOneBySlug($slug);

        if (!$page) {
            return $this->pageNotFoundResponse($slug);
        }

        if ($this->get('kernel')->isAdmin()) {
            $canEdit = false;
            if ($this->container->has('security.context')) {
                $securityContext = $this->get('security.context');
                $canEdit = $securityContext->isGrantedPageEdit($page);
            }

            if (!$canEdit) {
                return $this->pageNotFoundResponse($slug);
            }

            $pageVersion = $pageManager->getActivatedPageVersion();
            if (!$pageVersion && $this->getRequest()->get('version', false)) {
                $versionId = $this->getRequest()->get('version');
                $pageVersionRepository = $pageManager->getPageVersionRepository();
                $pageVersion = $pageVersionRepository->find($versionId);
                if (!$pageVersion) {
                    return $this->pageNotFoundResponse($slug);
                }

                $pageManager->setActivatedPageVersion($pageVersion);
            }

            if ($pageVersion && $page->getId() === $pageVersion->getPage()->getId()) {
                $page = $pageVersion->getPageData();
            } else {
                if (!$page = $pageManager->isPublished($page)) {
                    return $this->pageNotFoundResponse($slug);
                }
            }
        } else {
            if (!$page = $pageManager->isPublished($page)) {
                return $this->pageNotFoundResponse($slug);
            }
        }

        $rendererServiceId = sprintf('wf_cms.page_renderer.%s', $page->getRenderer());
        /**
         * @var PageRendererInterface
         */
        $renderer = $this->get($rendererServiceId);

        if (method_exists($renderer, 'setPage')) {
            $renderer->setPage($this->getRequest()->query->get('page'));
        }

        $settings = null;
        if ($request->query->has('settings')) {
            $settings = json_decode($request->query->get('settings'));
        }

        try {
            $ret = $renderer->render($page, $settings);
        } catch(NotFoundHttpException $e) {
            return $this->pageNotFoundResponse($slug);
        }

        if ($this->get('kernel')->isPublic()) {
            //quick (and really dirty) hack to fix links on public. On admin, where they're created, they have an additional /view in front of the link
            $ret->setContent(preg_replace('@(href="/)view/@i', '$1', $ret->getContent()));
        }

        $nextPublishedAt = $page->getNextPublishedAt();
        if (!empty($nextPublishedAt) && $nextPublishedAt instanceof \DateTime) {
            $ret->setExpires($nextPublishedAt->modify('+1 second'));
        } else {
            $ret->setExpires(new \DateTime('+5 minutes'));
        }

        return $ret;
    }

    public function pageNotFoundResponse($slug)
    {
        $content = '';
        $ret = new Response();
        if ($this->get('kernel')->isDeveloper()) {
            $content = $this->get('translator')->trans('wf_cms.page_missing', array('%slug%' => $slug), 'WfCmsBase');
            $ret->headers->set('WfNotFound', true);
        }

        $ret->setContent($content);

        return $ret;
    }

    /**
     */
    public function showErrorAction(Request $request, $slug)
    {
        $kernel = $this->container->get('kernel');
        $response = new Response();

        $ret = '';
        if ($kernel->isDeveloper()) {
            $ret = $this->container->get('translator')->trans('wf_cms.page_missing', array('%slug%' => $slug), 'WfCmsBase');
            $response->setPrivate();
        }

        $response->setContent($ret);

        return $response;
    }

    /**
     * @Template()
     */
    public function exporterIndexAction($editionSlug)
    {
        $editionRepository = $this->get('wf_cms.repository.edition')
            ;

        $edition = $editionRepository->findOneBySlug($slug);

        if (!$edition || !$edition->isPublished()) {
            throw $this->createNotFoundException();
        }

        $ret = array();

        return $ret;
    }

    /**
     * Renders only the <body> of an article
     */
    public function bodyAction($slug)
    {
        $response = $this->showAction($slug);

        $content = $response->getContent();
        $match = preg_match('@<body[^>]*>(.*)<\/body>@is', $content, $matches);

        if (!$match) {
            $response->setContent('');
        } else {
            $response->setContent($matches[1]);
        }

        return $response;
    }

    /**
     * @Template()
     */
    public function latestAction($_format, $page = 1) {
        $serializer = $this->container->get('serializer');
        $serializer->setGroups(array('list'));

        $articleRepository = $this->get('wf_cms.repository.article');
        $qb = $articleRepository->getLatestItemsQB(null, null, false);

        $pagerFactory = $this->get('wf_cms.pager.factory');
        $articlesPager = $pagerFactory->createPager($qb, $page);
        $articlesPager->setMaxPerPage(14);

        $ret = array();
        $ret['results'] = json_decode($serializer->serialize($articlesPager->getCurrentPageResults(), 'json'));
        $ret['pager'] = $this->get('wf_cms_admin.pager.renderer.search')->render($articlesPager, array(
            'routeName' => 'wfadmin_latest_articles',
            'routeParams' => array(
            )
        ));

        return new Response(json_encode($ret));

    }
}
