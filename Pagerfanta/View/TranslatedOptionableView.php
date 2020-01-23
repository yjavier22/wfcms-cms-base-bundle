<?php

namespace Wf\Bundle\CmsBaseBundle\Pagerfanta\View;

use Pagerfanta\View\ViewInterface;
use Pagerfanta\PagerfantaInterface;
use Symfony\Component\Translation\TranslatorInterface;
use WhiteOctober\PagerfantaBundle\View\DefaultTranslatedView;

class TranslatedOptionableView extends DefaultTranslatedView
{

    private $view;
    private $defaultOptions;
    private $translator;

    /**
     * Constructor.
     *
     * @param ViewInterface $view    A view.
     * @param array         $options An array of default options (optional).
     *
     * @api
     */
    public function __construct(ViewInterface $view, TranslatorInterface $translator, array $defaultOptions = array())
    {
        parent::__construct($view, $translator);
        $this->defaultOptions = $defaultOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function render(PagerfantaInterface $pagerfanta, $routeGenerator, array $options = array())
    {
        $options = array_merge($this->defaultOptions, $options);
        return parent::render($pagerfanta, $routeGenerator, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'translated_optionable';
    }
}