<?php

namespace Wf\Bundle\CmsBaseBundle\Menu;

use Knp\Menu\Renderer\ListRenderer;
use Knp\Menu\ItemInterface;
use Wf\Bundle\CmsBaseBundle\Entity\Category;
use Wf\Bundle\CmsBaseBundle\Entity\Page;

class  MenuRenderer extends ListRenderer
{
    protected $router;
    protected $httpKernel;

    public function setRouter($router)
    {
        $this->router = $router;
    }

    public function setKernel($httpKernel)
    {
        $this->httpKernel = $httpKernel;
    }

    public function render(ItemInterface $item, array $options = array())
    {
        // set current item before rendering to be able to use 'isCurrentAncestor'
        $item = $this->walkTree($item, function($item) use ($options) {
            $extras = $item->getExtras();
            switch ($extras['type']) {
                case 'category':
                    $url = $extras['details']['category'];

                    if (isset($options['currentItem'])
                        && $options['currentItem'] instanceof Category
                        && $options['currentItem']->getSlug() == $url) {
                        $item->setCurrent(true);
                    }

                    break;
                case 'url':
                    if (isset($options['currentItem'])
                        && $options['currentItem'] == $extras['details']['url']) {
                        $item->setCurrent(true);
                    }

                    break;
                case 'article':
                    $url = '';
                    if (!empty($extras['details']['article-url'])) {
                        $url = $extras['details']['article-url'];
                    }
                    $item->setUri('/' . $url . '.html');

                    if (isset($options['currentItem'])
                        && $options['currentItem'] instanceof Page
                        && $options['currentItem']->getSlug() == $url) {
                        $item->setCurrent(true);
                    }

                    break;
            }
            return $item;
        });


        return parent::render($item, $options);
    }

    private function walkTree($nodes, $callback = null)
    {
        foreach($nodes as $node) {
            $node = isset($callback) ? call_user_func_array($callback, array($node)) : $node;
            $children = $node->getChildren();
            if ($children) {
                $node->setChildren($this->walkTree($children, $callback));
            }
        }

        return $nodes;
    }

    protected function renderItem(ItemInterface $item, array $options)
    {
        // if we don't have access or this item is marked to not be shown
        if (!$item->isDisplayed()) {
            return '';
        }

        $extras = $item->getExtras();

        // create an array than can be imploded as a class list
        $class = (array) $item->getAttribute('class');

        switch ($extras['type']) {
            case 'category':
                $url = $extras['details']['category'];
                $class[] = $this->getCssClass($url);
                break;
            case 'url':
                $class[] = 'link';
                if (isset($extras['details']['url-external']) && $extras['details']['url-external']) {
                    $class[] = 'external';
                }
                break;
            case 'homepage':
                $item->setUri($this->router->generate('wf_homepage'));
                $class[] = 'homepage';
                break;
            case 'article':
                $url = '';
                if (!empty($extras['details']['article-url'])) {
                    $url = $extras['details']['article-url'];
                }
                $item->setUri('/' . $url . '.html');
                $class[] = 'link';
                break;
        }

        if ($item->isCurrent()) {
            $class[] = $options['currentClass'];
        } elseif ($item->isCurrentAncestor()) {
            $class[] = $options['ancestorClass'];
        }

        if ($item->hasChildren()) {
            $class[] = 'expandable';
        }

        if ($item->actsLikeFirst()) {
            $class[] = $options['firstClass'];
        }

        if ($item->actsLikeLast()) {
            $class[] = $options['lastClass'];
        }

        if (isset($extras['details']['css_class'])) {
            $class[] = $extras['details']['css_class'];
        }

        // retrieve the attributes and put the final class string back on it
        $attributes = $item->getAttributes();
        if (!empty($class)) {
            $attributes['class'] = implode(' ', $class);
        }

        // opening li tag
        $html = $this->format('<li'.$this->renderHtmlAttributes($attributes).'>', 'li', $item->getLevel(), $options);

        $methodName = 'render' . ucfirst($extras['type']);
        if (!method_exists($this, $methodName)) {
            $methodName = 'renderLink';
        }
        $html.= $this->$methodName($item, $options);

        // renders the embedded ul
        $childrenClass = (array) $item->getChildrenAttribute('class');
        $childrenClass[] = 'menu_level_'.$item->getLevel();

        $childrenAttributes = $item->getChildrenAttributes();
        $childrenAttributes['class'] = implode(' ', $childrenClass);

        $html .= $this->renderList($item, $childrenAttributes, $options);

        // closing li tag
        $html .= $this->format('</li>', 'li', $item->getLevel(), $options);

        return $html;
    }


    public function renderBoard(ItemInterface $item, array $options = array())
    {
        $extras = $item->getExtras();
        $board = $extras['details']['board'];

        $url = $this->router->generate('wf_page_show', array('slug' => $board));
        $ret = $this->httpKernel->render($url);

        return $ret;
    }

    public function renderText(ItemInterface $item, array $options = array())
    {
        return $this->renderSpanElement($item, $options);
    }

    protected function getCssClass($string)
    {
        return preg_replace('@[^a-z-]+@i', '-', $string);
    }

}