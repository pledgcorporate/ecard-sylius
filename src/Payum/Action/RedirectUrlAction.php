<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpRedirect;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrlInterface;
use Pledg\SyliusPaymentPlugin\RedirectUrl\EncoderInterface;
use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilderFactoryInterface;
use Psr\Log\LoggerInterface;

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
        string $pledgUrl,
        private LoggerInterface $logger,
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

        $redirectUrl = $this->getPurchaseUrl() . '?signature=' . $token;
        $this->logger->debug('PLEDG ' . __METHOD__, compact('redirectUrl'));

        throw new HttpRedirect($redirectUrl);
    }

    private function setPaymentDetails(RedirectUrlInterface $request, array $parameters, string $token): void
    {
        $this->logger->debug('PLEDG ' . __METHOD__, compact('request'));
        $this->logger->debug('PLEDG ' . __METHOD__, compact('parameters'));
        $this->logger->debug('PLEDG ' . __METHOD__, compact('token'));

        $redirectUrl = $this->getPurchaseUrl() . '?signature=' . $token;
        $this->logger->debug('PLEDG ' . __METHOD__, compact('redirectUrl'));

        $request->getPayment()->setDetails([
            'redirect_parameters' => $parameters,
            'redirect_url' => $redirectUrl,
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
