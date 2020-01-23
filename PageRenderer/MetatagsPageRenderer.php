<?php

namespace Wf\Bundle\CmsBaseBundle\PageRenderer;

use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MetaPageRenderer
 * Renders <meta> tags for the head.
 * Because the HTML assembler adds the contents to a DOMDocument,
 * rendering <meta> tags with the HTMLPageRenderer would give an empty result
 * (trying to inject <meta> tags in a <div>)
 * @package Wf\Bundle\CmsBaseBundle\PageRenderer
 */
class MetatagsPageRenderer
    implements PageRendererInterface
{
    public function render(Page $page, $moduleSettings = null)
    {
        $collection = $page->getModulesCollection();
        $content = '';
        foreach ($collection->getAllById('wfed/composite/metatags') as $module) {
            $content.= $module['html'] . "\n";
        }

        return new Response($content);
    }

} 