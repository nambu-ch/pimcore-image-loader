<?php
namespace ImageLoaderBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ImageLoaderExtension extends Extension {

    public function load(array $configs, ContainerBuilder $container) {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (array_key_exists('cache_buster', $config)) {
            $container->setParameter('image_loader', $config['cache_buster']);
            foreach ($config['cache_buster'] as $key => $value) {
                $container->setParameter('image_loader.cache_buster.' . $key, $config['cache_buster'][$key]);
            }
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');
    }

}
