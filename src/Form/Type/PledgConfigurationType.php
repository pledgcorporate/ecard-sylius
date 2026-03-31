<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Form\Type;

use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
            ->add(PledgGatewayFactory::API_KEY, TextType::class, [
                'label' => 'pledg_sylius_payment_plugin.api_key',
                'required' => false,
                'help' => 'pledg_sylius_payment_plugin.api_key_help',
            ])
            ->add(PledgGatewayFactory::API_SECRET, TextType::class, [
                'label' => 'pledg_sylius_payment_plugin.api_secret',
                'required' => false,
                'help' => 'pledg_sylius_payment_plugin.api_secret_help',
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add(PledgGatewayFactory::COMPANY_UID, TextType::class, [
                'label' => 'pledg_sylius_payment_plugin.company_uid',
                'required' => false,
                'help' => 'pledg_sylius_payment_plugin.company_uid_help',
            ])
            ->add(PledgGatewayFactory::WIDGET_PRODUCT_ENABLED, CheckboxType::class, [
                'label' => 'pledg_sylius_payment_plugin.widget_product',
                'required' => false,
            ])
            ->add(PledgGatewayFactory::WIDGET_CHECKOUT_ENABLED, CheckboxType::class, [
                'label' => 'pledg_sylius_payment_plugin.widget_checkout',
                'required' => false,
            ])
            ->add(PledgGatewayFactory::WIDGET_CATALOG_ENABLED, CheckboxType::class, [
                'label' => 'pledg_sylius_payment_plugin.widget_catalog',
                'required' => false,
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
                'required' => false,
            ])
            ->add(PledgGatewayFactory::SANDBOX, CheckboxType::class, [
                'label' => 'pledg_sylius_payment_plugin.sandbox',
                'required' => false,
                'help' => 'pledg_sylius_payment_plugin.sandbox_help',
            ])
        ;
    }
}
