<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pledg_sylius_payment_plugin');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('sandbox')->defaultTrue()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
