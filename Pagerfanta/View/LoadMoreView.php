<?php

namespace Wf\Bundle\CmsBaseBundle\Pagerfanta\View;

use Wf\Bundle\CmsBaseBundle\Pagerfanta\View\Template\LoadMoreTemplate;
use Pagerfanta\View\DefaultView;
use Symfony\Component\Translation\TranslatorInterface;

class LoadMoreView extends DefaultView
{
    protected function createDefaultTemplate()
    {
        return new LoadMoreTemplate();
    }

    protected function getDefaultProximity()
    {
        return 0;
    }

    public function getName()
    {
        return 'default_more';
    }
}