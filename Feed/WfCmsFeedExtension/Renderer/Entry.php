<?php

namespace Wf\Bundle\CmsBaseBundle\Feed\WfCmsFeedExtension\Renderer;

use Zend\Feed\Writer\Extension;
use DOMDocument;
use DOMElement;

class Entry extends Extension\AbstractRenderer
{
    /**
     * abstract function
     */
    protected function _appendNamespaces()
    {
    }

    public function render()
    {
        $this->_setSubtitle($this->dom, $this->base);
        $this->_setCategory($this->dom, $this->base);
        $this->_setAuthor($this->dom, $this->base);
        $this->_setTags($this->dom, $this->base);
    }

    protected function _setSubtitle(DOMDocument $dom, DOMElement $root)
    {
        $subtitle = $this->getDataContainer()->getSubtitle();
        if (!$subtitle) {
            return;
        }
        $el = $dom->createElement('subtitle');
        $text = $dom->createTextNode($subtitle);
        $el->appendChild($text);
        $root->appendChild($el);
    }

    protected function _setCategory(DOMDocument $dom, DOMElement $root)
    {
        $category = $this->getDataContainer()->getCategory();
        if (!$category) {
            return;
        }
        $el = $dom->createElement('category');
        $text = $dom->createTextNode($category);
        $el->appendChild($text);
        $root->appendChild($el);
    }

    protected function _setAuthor(DOMDocument $dom, DOMElement $root)
    {
        $author = $this->getDataContainer()->getAuthor();
        if (!$author) {
            return;
        }

        $text = $dom->createTextNode($author);

        $el = $dom->createElement('author');
        $el->appendChild($text);
        $root->appendChild($el);
    }

    /**
     * rendered differently for xml
     */
    protected function _setTags(DOMDocument $dom, DOMElement $root)
    {
        $tags = $this->getDataContainer()->getTags();
        if (!$tags) {
            return;
        }

        $containerName = 'tags';
        $elementName = 'tag';
        $attrName = 'name';

        $container = $dom->createElement($containerName);

        foreach ($tags as $tagName) {
            //create the tag
            $tag = $dom->createElement($elementName);
            $text = $dom->createTextNode($tagName['slug']);

            //add name attribute
            $attr = $dom->createAttribute($attrName);
            $value = $tagName['title'];
            $value = html_entity_decode($value);
            $value = strip_tags($value);
            $attr->value = $value;
            $tag->appendChild($attr);

            //append tag to container
            $tag->appendChild($text);
            $container->appendChild($tag);
        }

        $root->appendChild($container);
    }
}
