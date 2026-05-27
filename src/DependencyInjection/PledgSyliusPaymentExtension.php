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
        if (!$container->hasExtension('sylius_twig_hooks')) {
            return;
        }

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

        $this->registerStateMachineAbstractionIfMissing($container, $loader);

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

    /**
     * Sylius 1.13+ and Sylius 2 register this service; Sylius ≤1.12 needs sylius/state-machine-abstraction
     * (declared in composer.json) — we merge the same definitions when the app did not register the bundle.
     */
    private function registerStateMachineAbstractionIfMissing(ContainerBuilder $container, XmlFileLoader $loader): void
    {
        if ($container->has('sylius_abstraction.state_machine') || $container->hasAlias('sylius_abstraction.state_machine')) {
            return;
        }

        if (!class_exists(\Sylius\Abstraction\StateMachine\WinzouStateMachineAdapter::class)) {
            throw new \LogicException(
                'The package "sylius/state-machine-abstraction" (^1.13 || ^2.0) must be present so that ' .
                'Sylius\Abstraction\StateMachine\StateMachineInterface is available (bundled in Sylius 1.13+ and Sylius 2 via replace).'
            );
        }

        if (!$container->hasParameter('sylius_abstraction.state_machine.default_adapter')) {
            $container->setParameter('sylius_abstraction.state_machine.default_adapter', 'winzou_state_machine');
        }
        if (!$container->hasParameter('sylius_abstraction.state_machine.graphs_to_adapters_mapping')) {
            $container->setParameter('sylius_abstraction.state_machine.graphs_to_adapters_mapping', []);
        }

        $loader->load('state_machine_abstraction_fallback.xml');
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration();
    }
}
