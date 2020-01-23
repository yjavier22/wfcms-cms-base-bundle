<?php

namespace Wf\Bundle\CmsBaseBundle\Pagerfanta\View\Template;
use Pagerfanta\View\Template\TwitterBootstrapTemplate;

class LoadMoreTemplate extends TwitterBootstrapTemplate
{
    static protected $defaultOptions = array(
        'more_message'        => 'See more',
        'next_disabled_href'  => '#',
        'dots_message'        => '',
        'dots_href'           => '#',
        'css_container_class' => 'see-more-nav pagination',
        'css_more_class'      => 'see-more',
        'css_disabled_class'  => 'disabled',
        'css_active_class'    => 'active',
        'span_template'      => '<span class="%class%">%text%</span>'
    );

    public function previousDisabled()
    {
        return '';
    }

    public function previousEnabled($page)
    {
        return '';
    }

    public function nextDisabled()
    {
        return $this->generateSpan($this->option('css_disabled_class'), $this->option('more_message'));
    }

    public function nextEnabled($page)
    {
        return $this->pageWithTextAndClass($page, $this->option('more_message'), $this->option('css_more_class'));
    }

    public function page($page)
    {
        return '';
    }

    public function first()
    {
        return '';
    }

    public function last($page)
    {
        return '';
    }

    public function current($page)
    {
        return '';
    }

    public function separator()
    {
        return '';
    }

    public function pageWithTextAndClass($page, $text, $class)
    {
        $href = $this->generateRoute($page);

        return $this->li($class, $href, $text);
    }

    private function li($class, $href, $text)
    {
        $liClass = $class ? sprintf(' class="%s"', $class) : '';

        return sprintf('<li%s><a href="%s">%s</a></li>', $liClass, $href, $text);
    }

    private function generateSpan($class, $page)
    {
        $search = array('%class%', '%text%');
        $replace = array($class, $page);

        return str_replace($search, $replace, $this->option('span_template'));
    }
}