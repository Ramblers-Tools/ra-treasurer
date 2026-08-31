<?php

/**
 * 20/08/26 created by component-creator
 */
defined('_JEXEC') or die;

use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Ramblers\Component\Ra_treasurer\Administrator\Extension\Ra_treasurerComponent;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The Ra_treasurer service provider.
 *
 * @since  1.0.0
 */
return new class implements ServiceProviderInterface {

    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function register(Container $container) {

        $container->registerServiceProvider(new CategoryFactory('\\Ramblers\\Component\\Ra_treasurer'));
        $container->registerServiceProvider(new MVCFactory('\\Ramblers\\Component\\Ra_treasurer'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Ramblers\\Component\\Ra_treasurer'));
        $container->registerServiceProvider(new RouterFactory('\\Ramblers\\Component\\Ra_treasurer'));

        $container->set(
                ComponentInterface::class,
                function (Container $container) {
                    $component = new Ra_treasurerComponent($container->get(ComponentDispatcherFactoryInterface::class));

                    $component->setRegistry($container->get(Registry::class));
                    $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                    $component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
                    $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                    return $component;
                }
        );
    }
};
