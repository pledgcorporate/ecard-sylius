<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpRedirect;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrlInterface;
use Pledg\SyliusPaymentPlugin\RedirectUrl\EncoderInterface;
use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilderFactoryInterface;

class RedirectUrlAction implements ActionInterface
{
    /** @var ParamBuilderFactoryInterface */
    protected $paramBuilderFactory;

    /** @var EncoderInterface */
    protected $encoder;

    /** @var string */
    protected $pledgUrl;

    public function __construct(
        ParamBuilderFactoryInterface $paramBuilderFactory,
        EncoderInterface $encoder,
        string $pledgUrl
    ) {
        $this->paramBuilderFactory = $paramBuilderFactory;
        $this->encoder = $encoder;
        $this->pledgUrl = $pledgUrl;
    }

    /**
     * @param RedirectUrlInterface|mixed $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var RedirectUrlInterface $request */
        $parameters = $this->paramBuilderFactory->fromRedirectUrlRequest($request)->build();
        $token = $this->encoder->encode($parameters, $request->getMerchant()->getSecret());

        $this->setPaymentDetails($request, $parameters, $token);

        throw new HttpRedirect($this->getPurchaseUrl() . '?signature=' . $token);
    }

    private function setPaymentDetails(RedirectUrlInterface $request, array $parameters, string $token): void
    {
        $request->getPayment()->setDetails([
            'redirect_parameters' => $parameters,
            'redirect_url' => $this->getPurchaseUrl() . '?signature=' . $token,
        ]);
    }

    public function supports($request)
    {
        return $request instanceof RedirectUrlInterface;
    }

    protected function getPurchaseUrl(): string
    {
        return sprintf('%s/purchase', $this->pledgUrl);
    }
}
