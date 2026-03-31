<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\DependencyInjection;

use Pledg\SyliusPaymentPlugin\PledgUrl;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Yaml\Yaml;

final class PledgSyliusPaymentExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $path = __DIR__ . '/../Resources/config/twig_hooks.yaml';
        if (!is_file($path)) {
            return;
        }

        $parsed = Yaml::parseFile($path);
        if (\is_array($parsed) && isset($parsed['sylius_twig_hooks'])) {
            $container->prependExtensionConfig('sylius_twig_hooks', $parsed['sylius_twig_hooks']);
        }
    }

    public function load(array $config, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $config);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter(
            'pledg_sylius_payment_plugin.front_url',
            PledgUrl::frontUrl($config['sandbox'])
        );
        $container->setParameter(
            'pledg_sylius_payment_plugin.back_url',
            PledgUrl::backUrl($config['sandbox'])
        );

        $loader->load('services.xml');
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration();
    }
}
