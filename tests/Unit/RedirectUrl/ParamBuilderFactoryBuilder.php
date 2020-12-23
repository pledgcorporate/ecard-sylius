<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\RedirectUrl;


use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilderFactory;
use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilderFactoryInterface;
use Prophecy\Prophet;
use Symfony\Component\Routing\RouterInterface;

class ParamBuilderFactoryBuilder
{
    /** @var RouterInterface */
    private $router;

    public function __construct()
    {
        $prophet = new Prophet();
        $router = $prophet->prophesize(RouterInterface::class);
        $router
            ->generate('pledg_sylius_payment_plugin_webhook_notification', [], RouterInterface::ABSOLUTE_URL)
            ->willReturn('http://127.0.0.1/pledg/notification');
        $this->router = $router->reveal();
    }

    public function build(): ParamBuilderFactoryInterface
    {
        return new ParamBuilderFactory($this->router);
    }
}
