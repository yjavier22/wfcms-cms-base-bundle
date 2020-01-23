<?php

namespace Wf\Bundle\CmsBaseBundle\PageRenderer;

use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Response;

interface PageRendererInterface
{
    /**
     * @param Page $page
     * @return Response
     */
    public function render(Page $page, $moduleSettings = null);
}