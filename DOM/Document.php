<?php

namespace Wf\Bundle\CmsBaseBundle\DOM;

use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\DomCrawler\Crawler;

class Document extends Crawler
{
    /**
     * Filters the list of nodes with an XPath expression.
     *
     * @param string $xpath An XPath expression
     *
     * @return Crawler A new instance of Crawler with the filtered list of nodes
     *
     * @api
     */
    public function nfilterXPath($xpath)
    {
        $domxpath = new \DOMXPath($this->getFirstDOMNode()->ownerDocument);

        return $domxpath->query($xpath);
    }

    /**
     * Filters the list of nodes with a CSS selector.
     *
     * This method only works if you have installed the CssSelector Symfony Component.
     *
     * @param string $selector A CSS selector
     *
     * @return Crawler A new instance of Crawler with the filtered list of nodes
     *
     * @throws \RuntimeException if the CssSelector Component is not available
     *
     * @api
     */
    public function nfilter($selector)
    {
        if (!class_exists('Symfony\\Component\\CssSelector\\CssSelector')) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException('Unable to filter with a CSS selector as the Symfony CssSelector is not installed (you can use filterXPath instead).');
            // @codeCoverageIgnoreEnd
        }

        return $this->nfilterXPath(CssSelector::toXPath($selector));
    }

    public function addContentForSelector($selector, $content, $limit = null, $charset = 'UTF-8')
    {
        $nodeList = $this->nfilter($selector);
        if (!$nodeList->length) {
            return;
        }

        for ($i = 0; $i < $nodeList->length; $i++) {
            if (isset($limit) && $i > $limit) {
                break;
            }

            $node = $nodeList->item($i);

            $newDocument = new static();
            $newDocument->addHTMLContent($content);

            $newDocument = $newDocument->filter('body > *');
            if (!count($newDocument)) {
                //echo 'empty new node';
                continue;
            }

            foreach ($newDocument as $newNode) {
                $importedNewNode = $node->ownerDocument->importNode($newNode, true);
                $node->appendChild($importedNewNode);
            }
            //echo '<xmp>' . $selector . "\n\n" . $node->ownerDocument->saveHTML() . '</xmp>';

        }
    }

    public function getFirstDOMNode()
    {
        foreach ($this as $node) {
            return $node;
        }
    }

    public function saveHTML($fullHtml = true)
    {
        $htmlNode = $this->getFirstDOMNode();
        $document = $htmlNode->ownerDocument;
        if ($fullHtml) {
            return $document->saveHTML();
        } else {
            $first = $this->nfilter('body > *');
            $html = '';
            foreach ($first as $node) {
                $html.= $document->saveHTML($node);
            }

            return $html;
        }
    }
}
