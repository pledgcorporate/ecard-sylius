<?php


namespace Pledg\SyliusPaymentPlugin\Payum\Action;


use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpRedirect;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrlInterface;
use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilderFactoryInterface;

class RedirectUrlAction implements ActionInterface
{
    /** @var ParamBuilderFactoryInterface */
    protected $paramBuilderFactory;

    public function __construct(ParamBuilderFactoryInterface $paramBuilderFactory)
    {
        $this->paramBuilderFactory = $paramBuilderFactory;
    }

    /**
     * @param RedirectUrlInterface|mixed $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $parameters = $this->paramBuilderFactory->fromRedirectUrlRequest($request)->build();

        throw new HttpRedirect('https://staging.front.ecard.pledg.co/purchase?' . http_build_query($parameters));
    }

    public function supports($request)
    {
        return $request instanceof RedirectUrlInterface;
    }

}
