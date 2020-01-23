<?php

namespace Wf\Bundle\CmsBaseBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;


/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class WfCmsBaseExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('repositories.xml');
        $loader->load('controllers.xml');
        $loader->load('cms_configuration.xml');
        $loader->load('templating.xml');
        $loader->load('page_modules.xml');
        $loader->load('page_listeners.xml');
        $loader->load('twig_extensions.xml');
        $loader->load('router.xml');
        $loader->load('frontend_cache.xml');
        $loader->load('menu.xml');
        $loader->load('category.xml');
        $loader->load('sitemap.xml');
        $loader->load('publish.xml');
        $loader->load('pager.xml');
        $loader->load('rating.xml');
        $loader->load('serializer.xml');

        if ($config['poll']) {
            $loader->load('poll.xml');
        }

        if ($config['article_mail']) {
            $loader->load('article_mail.xml');
        }

        $loader->load('search.xml');
        $loader->load('wf_elastica.xml');
        $this->handleSearchConfig($container);

        $this->handleEntityConfig($config, $container);
    }

    protected function handleSearchConfig(ContainerBuilder $container)
    {
        $indexName = $container->getParameter('fos_index_name');

        //general index
        $finderDefinition = $container->getDefinition('wf_cms.search.general_finder');
        $finderDefinition->replaceArgument(0, new Reference(sprintf('fos_elastica.index.%s', $indexName)));
        $finderDefinition->replaceArgument(1, new Reference(sprintf('fos_elastica.elastica_to_model_transformer.collection.%s', $indexName)));

        $findables = array('article', 'image', 'audio', 'video', 'poll', 'file');
        foreach($findables as $contentType) {
            $finderDefinition = $container->getDefinition(sprintf('wf_cms.search.%s_finder', $contentType));
            $finderDefinition->replaceArgument(0, new Reference(sprintf('fos_elastica.index.%s.%s', $indexName, $contentType)));
            $finderDefinition->replaceArgument(1, new Reference(sprintf('fos_elastica.elastica_to_model_transformer.%s.%s', $indexName, $contentType)));
        }

        foreach ($container->findTaggedServiceIds('wf_elastica.provider') as $providerId => $providerAttributes) {
            $typeName = $providerAttributes[0]['type'];
            $entityClass = $providerAttributes[0]['entity'];
            $providerDefinition = $container->getDefinition($providerId);
            $tagAttributes = array(
                'index' => $indexName,
                'type' => $typeName
            );
            $providerDefinition->addTag('fos_elastica.provider', $tagAttributes);
            $providerDefinition->replaceArgument(0, new Reference(sprintf('fos_elastica.object_persister.%s.%s', $indexName, $typeName)));
            $providerDefinition->addMethodCall('setEntityClass', array($entityClass));
        }
    }

    protected function handleEntityConfig($config, ContainerBuilder $container)
    {
        $entityNamespace = $config['bundle']['namespace'] . '\\' . $config['entity']['namespace'];
        foreach ($config['entity']['name'] as $name=>$class) {
            $fullClass = $entityNamespace . '\\' . $class;
            $container->setParameter(sprintf('wf_cms.entity.%s.class', $name), $fullClass);

            $repositoryId = sprintf('wf_cms.repository.%s', $name);
            if (!$container->hasDefinition($repositoryId)) {
                $repositoryService = new Definition($fullClass);
                $repositoryService->setFactoryService('doctrine.orm.default_entity_manager');
                $repositoryService->setFactoryMethod('getRepository');
                $repositoryService->setArguments(array($fullClass));

                $container->setDefinition($repositoryId, $repositoryService);
            }
        }
    }

}
