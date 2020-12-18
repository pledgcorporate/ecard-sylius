<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Action;


use Pledg\SyliusPaymentPlugin\Payum\Action\RedirectUrlAction;
use Pledg\SyliusPaymentPlugin\RedirectUrl\Encoder;
use Pledg\SyliusPaymentPlugin\RedirectUrl\EncoderInterface;
use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilderFactory;
use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilderFactoryInterface;

class RedirectUrlActionBuilder
{
    /** @var ParamBuilderFactoryInterface */
    private $paramBuilderFactory;

    /** @var EncoderInterface */
    private $encoder;

    public function __construct()
    {
        $this->paramBuilderFactory = new ParamBuilderFactory();
        $this->encoder = new Encoder();
    }

    public function build(): RedirectUrlAction
    {
        return new RedirectUrlAction($this->paramBuilderFactory, $this->encoder);
    }
}
