<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Form\Type;

use Pledg\SyliusPaymentPlugin\Form\Type\PledgConfigurationType;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Symfony\Component\Form\Test\TypeTestCase;

class PledgConfigurationTypeTest extends TypeTestCase
{
    /** @test */
    public function it_submit_valid_data(): void
    {
        $formData = [
            PledgGatewayFactory::IDENTIFIER => 'mer_aee4846c-ac62-4835-8adf-bea9f8737144',
            PledgGatewayFactory::SECRET => 'aIDZLuoAdK8NAqoFIFPBao72WEQ6jrWMvYwaXaiO',
            PledgGatewayFactory::PRICE_RANGE_MIN => null,
            PledgGatewayFactory::PRICE_RANGE_MAX => null,
            PledgGatewayFactory::RESTRICTED_COUNTRIES => ['FR'],
        ];

        $form = $this->factory->create(PledgConfigurationType::class);

        $form->submit($formData);

        // This check ensures there are no transformation failures
        self::assertTrue($form->isSynchronized());
        self::assertSame($formData, $form->getData());
    }
}
