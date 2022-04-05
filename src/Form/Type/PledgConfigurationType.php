<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Form\Type;

use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PledgConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(PledgGatewayFactory::IDENTIFIER, TextType::class, [
                'label' => 'pledg_sylius_payment_plugin.identifier',
            ])
            ->add(PledgGatewayFactory::SECRET, TextType::class, [
                'label' => 'pledg_sylius_payment_plugin.secret',
            ])
            ->add(PledgGatewayFactory::PRICE_RANGE_MIN, NumberType::class, [
                'label' => 'pledg_sylius_payment_plugin.price_range_min',
                'required' => false,
                'scale' => 0,
            ])
            ->add(PledgGatewayFactory::PRICE_RANGE_MAX, NumberType::class, [
                'label' => 'pledg_sylius_payment_plugin.price_range_max',
                'required' => false,
                'scale' => 0,
            ])
            ->add(PledgGatewayFactory::RESTRICTED_COUNTRIES, CountryType::class, [
                'label' => 'pledg_sylius_payment_plugin.restricted_countries',
                'multiple' => true,
            ])
        ;
    }
}
