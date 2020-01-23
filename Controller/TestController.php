<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @author gk
 */
class TestController extends Controller
{
    /**
     * @Template()
     * @Cache(smaxage="30")
     */
    public function cacheAction()
    {
        return array('date' => new \DateTime());
    }

    /**
     * @Template()
     * @Cache(smaxage="15")
     */
    public function cachePartAction()
    {
        return array('date' => new \DateTime());
    }

    /**
     * @Template("WfCmsBaseBundle:Test:cachePart.html.twig")
     */
    public function cachePart2Action()
    {
        return array('date' => new \DateTime());
    }


}