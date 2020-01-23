<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class NowjsController extends Controller
{
    /**
    * @Template()
    */
    public function nowjsAction()
    {

        return array(
           'nodejs_host' => $this->container->getParameter('admin_nodejs.host'),
           'nodejs_port' => $this->container->getParameter('admin_nodejs.port')
           );
    }
}