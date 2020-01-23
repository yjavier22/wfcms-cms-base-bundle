<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class AdvertisementController extends Controller
{
    /**
     * create ad config
     */
    public function pagesAction()
    {
        $pages = $this->get('wf_cms.repository.advertisement_page')->findByActive(true);
        $pages = $this->get('jms_serializer')->serialize($pages, 'json');

        $ret = new Response($this->get('templating')->render('WfCmsBaseBundle:Advertisement:pages.js.twig', array('pages' => $pages)));
        $ret->headers->set('Content-Type', 'text/javascript');
        return $ret;
    }
}
