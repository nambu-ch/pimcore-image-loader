<?php
namespace ImageLoaderBundle\DependencyInjection;
use Symfony\Component\Config\Definition\Builder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface {

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new Builder\TreeBuilder('image_loader');
        $rootNode = $treeBuilder->getRootNode();

        //@formatter:off
        $rootNode
            ->children()
                ->arrayNode('cache_buster')
                    ->children()
                        ->booleanNode('disabled')->end()
                    ->end()
                ->end()
                ->booleanNode('lazyloading')->end()
            ->end();
        //@formatter:on

        return $treeBuilder;
    }

}
