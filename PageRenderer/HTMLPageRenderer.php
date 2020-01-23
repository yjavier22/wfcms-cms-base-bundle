<?php

namespace Wf\Bundle\CmsBaseBundle\PageRenderer;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Wf\Bundle\CmsBaseBundle\Templating\HTMLPageAssembler;
use Symfony\Component\HttpFoundation\Response;

class HTMLPageRenderer
    implements PageRendererInterface
{
    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var HTMLPageAssembler
     */
    protected $assembler;

    public function __construct(EngineInterface $templating, HTMLPageAssembler $assembler)
    {
        $this->templating = $templating;
        $this->assembler = $assembler;
    }

    public function render(Page $page, $moduleSettings = null)
    {
        $ret = new Response($this->templating->render($page->getFullEditorTemplatePath() . '.html.twig', array(
            'editor' => false,
            'page' => $page,
            'category' => $page->getCategory(),
            'bodyscripts' => $page->getJavascripts(),
            'settings' => $page->getSettings()
        )));

        $content = $this->assembler->renderTemplate($ret->getContent(), $page->getHTML());

        $ret->setContent($content);

        return $ret;
    }

} 