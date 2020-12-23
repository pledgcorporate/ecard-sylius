<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PledgConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('identifier', TextType::class, [
                'label' => 'pledg_sylius_payment_plugin.identifier',
            ])
            ->add('secret', TextType::class, [
                'label' => 'pledg_sylius_payment_plugin.secret',
            ]);
    }
}
