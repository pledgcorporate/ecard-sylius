<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Action;


use Pledg\SyliusPaymentPlugin\Payum\Action\RedirectUrlAction;
use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilderFactory;
use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilderFactoryInterface;

class RedirectUrlActionBuilder
{
    /** @var ParamBuilderFactoryInterface */
    private $paramBuilderFactory;

    public function __construct()
    {
        $this->paramBuilderFactory = new ParamBuilderFactory();
    }

    public function build(): RedirectUrlAction
    {
        return new RedirectUrlAction($this->paramBuilderFactory);
    }
}
