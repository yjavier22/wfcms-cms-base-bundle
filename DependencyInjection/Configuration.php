<?php

namespace Wf\Bundle\CmsBaseBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    private $entityNames = array(
            'edition',
            'category',
            'page',
            'page_version',
            'page_article',
            'page_board',
            'page_homepage',
            'page_sidebar',
            'page_metatags',
            'page_edit',
            'image',
            'video',
            'audio',
            'file',
            'tag',
            'page_tag',
            'gallery',
            'imported_article',
            'user',
            'group',
            'comment',
            'thread',
            'poll',
            'poll_option',
            'page_slug',
            'page_metadata',
            'menu',
            'page_listing',
            'page_listing_template',
            'advertisement_banner',
            'advertisement_page',
            'page_author',
        );

    private $associatedEntities = array(
        'Wf\\Bundle\\BlogBundle\\WfBlogBundle' => array(
            'blog',
        ),
    );

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('wf_cms_base');

        $rootNode->append($this->addEntityNode());
        $rootNode->append($this->addBundleNode());

        $rootNode->children()
            ->booleanNode('poll')->defaultTrue()->end()
            ->booleanNode('article_mail')->defaultTrue()->end();

        return $treeBuilder;
    }

    protected function addBundleNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('bundle');

        $node
            ->isRequired()
            ->children()
                ->scalarNode('namespace')
                    ->isRequired()
                ->end()
                ;

        return $node;
    }

    protected function addEntityNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('entity');

        $node
            ->addDefaultsIfNotSet()
            ;

        $node
            ->children()
                ->scalarNode('namespace')
                    ->defaultValue('Entity');

        $entityNameNode = $node
            ->children()
                ->arrayNode('name')
                    ->addDefaultsIfNotSet()
                ;

        $this->addEntities($this->entityNames, $entityNameNode);

        foreach($this->associatedEntities as $bundleClass => $entities) {
            if (class_exists($bundleClass)) {
                $this->addEntities($entities, $entityNameNode);
            }
        }

        return $node;
    }

    protected function addEntities($entityNames, $node)
    {
        foreach ($entityNames as $entityName) {
            $this->addEntityName($node, $entityName);
        }
    }

    protected function addEntityName($node, $name, $value = null)
    {
        if (is_null($value)) {
            $values = explode('_', $name);
            foreach ($values as &$value) {
                $value = ucfirst($value);
            }

            $value = implode('', $values);
        }

        $node->children()
                ->scalarNode($name)
                    ->defaultValue($value)
                    ;
    }

}
