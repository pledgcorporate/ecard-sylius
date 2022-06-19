<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\DependencyInjection;

use Pledg\SyliusPaymentPlugin\PledgUrl;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class PledgSyliusPaymentExtension extends Extension
{
    public function load(array $config, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $config);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter(
            'pledg_sylius_payment_plugin.front_url',
            $config['sandbox'] ? PledgUrl::SANDBOX_FRONT : PledgUrl::PROD_FRONT
        );
        $container->setParameter(
            'pledg_sylius_payment_plugin.back_url',
            $config['sandbox'] ? PledgUrl::SANDBOX_BACK : PledgUrl::PROD_BACK
        );

        $loader->load('services.xml');
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration();
    }
}
