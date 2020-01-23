<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Wf\Bundle\CmsBaseBundle\Manager\DomainManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Wf\Bundle\CmsBaseBundle\Form\Type\ArticleMailType;

class ArticleController extends Controller
{

    public function showAction($articleSlug, Request $request)
    {
        $pageRepository = $this->get('wf_cms.repository.page');
        if (!$this->container->get('kernel')->isAdmin()) {
            $categoryDomains = $this->container->getParameter('cms_category_domains');
            $articleSlug = DomainManager::addPrefix($categoryDomains, $articleSlug);

            $redirect = $this->manageDomainRedirects($articleSlug);
            if ($redirect instanceof Response) {
                return $redirect;
            }
            $page = $redirect;
        } else {
            $page = $pageRepository->findOneBySlug($articleSlug);
        }

        $pageManager = $this->get('wf_cms.page_manager');
//        $page = $pageRepository->findOneBySlug($articleSlug);
        if (!$page) {
            return $this->pageNotFoundResponse($articleSlug);
        }

        $canEdit = false;
        if ($this->container->has('security.context')) {
            $securityContext = $this->get('security.context');
            if (method_exists($securityContext, 'isGrantedPageEdit')) {
                $canEdit = $securityContext->isGrantedPageEdit($page);
            }
        }

        $version = $canEdit ? $request->get('version', false) : null;

        $originalSlug = $page->getVersion() ? $page->getVersion()->getPage()->getSlug() : $page->getSlug();
        if (!$canEdit && !$pageManager->isPublished($page)) {
            return $this->pageNotFoundResponse($articleSlug);
        }
        if ($page->getSlug() != $originalSlug || $page->needsRedirect()) {//pageManager->isPublished set another current page version, and the slug was changed due to duplication; 301 redirect to new slug
            return $this->redirect($this->get('wf_cms.content_router')->generate($page), 301);
        }

        $pageVersion = $pageManager->getActivatedPageVersion();
        if (!$pageVersion && $version) {
            $versionId = $request->get('version');
            $pageVersionRepository = $pageManager->getPageVersionRepository();
            $pageVersion = $pageVersionRepository->find($versionId);
            if (!$pageVersion) {
                return $this->pageNotFoundResponse($articleSlug);
            }

            $pageManager->setActivatedPageVersion($pageVersion);
        }

        $pageSlug = $page->getSlug();
        if ($pageVersion && $page->getId() === $pageVersion->getPage()->getId()) {
            $page = $pageManager->getVersionPageData($pageVersion);
        }

        $category = $page->getCategory();
        if (!$category->getTitle()) {//re-load category lost in page version
            $categoryRepository = $this->container->get('wf_cms.repository.category');
            $category = $categoryRepository->find($category->getId());
        }

        //$author = $page->getAuthor();
        
        $authors = $page->getAuthors();
         
        if ($author && $author->getId() && !$author->getUsername()) {
            $userRepository = $this->container->get('wf_cms.repository.user');
            $author = $userRepository->find($author->getId());
            $page->setAuthor($author);
        }

        if (!$category) {
            return $this->pageNotFoundResponse($articleSlug);
        }

        $mainCategory = $category;
        $bodyClass = 'section-' . $category->getSlug();
        $categoryParent = $category->getParent();
        if (!empty($categoryParent)) {
            $bodyClass = 'section-' . $categoryParent->getSlug() . ' ' . $bodyClass;
            $mainCategory = $categoryParent;
        }
        $bodyClass = preg_replace('@[^a-z- ]+@i', '-', $bodyClass);

        $data = array(
            'authors' => $authors,
            'page' => $page,
            'pageSlug' => $pageSlug,
            'pageVersion' => $version,
            'category' => $category,
            'mainCategory' => $mainCategory,
            'bodyClass' => $bodyClass,
            'bodyscripts' => $page->getJavascripts(),
            'settings' => $page->getSettings(),
        );

        $trackingUrl = $this->container->getParameter('wf_tracking.nodejs.url') . 'track/%s/article-' . $page->getId();
        $data['trackingUrl'] = sprintf($trackingUrl, 'pageviews');
        $data['commentTrackingUrl'] = sprintf($trackingUrl, 'comments');
        $data['shareTrackingUrl'] = sprintf($trackingUrl, 'shares');

        if ($request->get('theme', null) == 'print') {
            return $this->render('WfCmsBaseBundle:Article:show_print.html.twig', $data);
        }

        $articleTemplate = $category->getArticleTemplate();
        if (!$articleTemplate) {
            if ($category->getParent() && $category->getParent()->getArticleTemplate()) {
                $articleTemplate = $category->getParent()->getArticleTemplate();
            } else {
                $articleTemplate = 'show';
            }
        }

        return $this->render(sprintf('WfCmsBaseBundle:Article:%s.html.twig', $articleTemplate), $data);
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
     * @Template()
     */
    public function getLatestAction($page)
    {
        $pageRepository = $this->get('wf_cms.repository.page_article');
        $ret['results'] = $pageRepository->getLatestPublished();
        return $ret;
    }

    protected function manageDomainRedirects($articleSlug) {
        $categoryDomains = $this->container->getParameter('cms_category_domains');
        $mainDomain = $this->container->getParameter('cms_main_domain');
        $curentHost = $this->getRequest()->getHost();

        $pageRepository = $this->get('wf_cms.repository.page');
        $page = $pageRepository->findOneBySlug($articleSlug);
        if (!$page) {
            foreach ($categoryDomains as $categorySlug => $domainName) {
                if ($curentHost != $domainName) {
                    continue;
                }
                $articleSlug = preg_replace('@^'.$categorySlug.'/@i', '', $articleSlug);
            }

            $page = $pageRepository->findOneBySlug($articleSlug);
            if (!$page) {
                throw $this->createNotFoundException($articleSlug);
            }
        }

        $category = $page->getCategory();

        if (!$category) {
            throw new HttpException(404);
        }

        $categoryParent = $category->getParent();
        $slug = $category->getSlug();
        if (!empty($categoryParent)) {
          $slug = $categoryParent->getSlug();
        }


        foreach ($categoryDomains as $categorySlug => $domainName) {
          if ($slug == $categorySlug && $curentHost != $domainName) {
                $articleSlug = trim(preg_replace('@^'.$slug.'@', '' ,$articleSlug), '/');
            return $this->redirect('http://'.$domainName . '/' .$articleSlug . '.html', 301);
          }
          if ($slug != $categorySlug && $curentHost == $domainName) {
            return $this->redirect('http://'.$mainDomain . '/' . $articleSlug . '.html', 301);
          }
        }

        return $page;
    }

    /**
     * @Template()
     */
    public function mailAction(Request $request)
    {
        $form = $this->createForm(new ArticleMailType());

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                //send mail with article
                $data = $form->getData();
                $host = $request->getHost();

                $article = $this->get('wf_cms.repository.page_article')
                    ->find($data['article_id']);

                // render email body
                $body = $this->renderView('WfCmsBaseBundle:Article:mailBody.html.twig', array(
                    'host' => $host,
                    'article' => $article,
                    'sender' => $data['sender_name'],
                    'comment' => $data['comment'],
                    'date' => $article->getPublishedAt()->format('l, d/m/Y - H:i'),
                ));

                $subject = $this->get('translator')->trans('mail.subject', array(
                    '%article_title%' => $article->getTitle()
                ), 'WfCms');

                // create the email
                $message = \Swift_Message::newInstance()
                    ->setContentType("text/html")
                    ->setSubject($subject)
                    ->setFrom($this->container->getParameter('wf_cms.article.send.from.email'), $this->container->getParameter('wf_cms.article.send.from.name'))
                    ->setTo($data['receiver_email'])
                    ->setBody($body)
                ;

                $this->get('mailer')->send($message);

                return new Response('{"error_code": 0}');
            }
        }

        return array(
            'form' => $form->createView()
        );
    }
}
