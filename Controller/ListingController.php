<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;

/**
 * listing actions
 *
 * @author cv
 */
class ListingController extends Controller
{
    /**
     * @Template()
     */
    public function mostAction($type, $page = 1)
    {
        return array(
            'type' => $type,
        );
    }
}