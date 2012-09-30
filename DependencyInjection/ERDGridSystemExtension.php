<?php

namespace ERD\GridSystemBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension; 
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\FileLocator;

use ERD\GridSystemBundle\DependencyInjection\GridSystemRepositoryFactory;

/**
 * This is the class that loads and manages your bundle configuration
 * 
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class ERDGridSystemExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {   
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
            
        $config = array();
        foreach($configs as $c) { $config = array_merge($config, $c); }

        //Set up a factory service which, in turn, will be called when syfmony tries to build
        //the repository service. This is easier because service definitions can't contain full
        //objects (no predictable way to represent them as a string), just arrays or references 
        //to other services. 
        //
        //So rather than making a service for each and every repository component and then 
        //passing those around by service id, I just set the factory with the limited config
        //info it needs to do all that building behind the scenes.
        $paths = (isset($config['grid_files'])) ? (array) $config['grid_files'] : array();
        $locator = new Reference('file_locator');
        
        $def = $container->getDefinition('erd_grid_system.repository_factory');
        $def->setArguments(array($paths, $locator));
        $container->setDefinition('erd_grid_system.repository_factory', $def);

    }
}