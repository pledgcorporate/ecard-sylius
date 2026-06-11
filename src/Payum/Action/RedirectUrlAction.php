<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Payum\Action;

use Symfony\Component\Routing\RouterInterface;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpRedirect;

use Psr\Log\LoggerInterface;

use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrlInterface;
use Pledg\SyliusPaymentPlugin\RedirectUrl\EncoderInterface;
use Pledg\SyliusPaymentPlugin\RedirectUrl\ParamBuilderFactoryInterface;
use Pledg\SyliusPaymentPlugin\Provider\PaymentMethodProviderInterface;
use Pledg\SyliusPaymentPlugin\Payum\Factory\PledgGatewayFactory;
use Pledg\SyliusPaymentPlugin\Provider\PledgGatewayConfigReader;

class RedirectUrlAction implements ActionInterface
{
    /** @var ParamBuilderFactoryInterface */
    protected $paramBuilderFactory;

    /** @var EncoderInterface */
    protected $encoder;

    public function __construct(
        ParamBuilderFactoryInterface $paramBuilderFactory,
        EncoderInterface $encoder,
        private PaymentMethodProviderInterface $paymentMethodProvider,
        private PledgGatewayConfigReader $pledgGatewayConfigReader,
        private RouterInterface $router,
        private LoggerInterface $logger,
    ) {
        $this->paramBuilderFactory = $paramBuilderFactory;
        $this->encoder = $encoder;
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

        $this->logger->debug('PLEDG ' . __METHOD__, compact('request'));
        $this->logger->debug('PLEDG ' . __METHOD__, compact('parameters'));
        $this->logger->debug('PLEDG ' . __METHOD__, compact('token'));

        $redirectUrl = $this->router->generate('sylius_shop_account_order_index');
        $this->logger->debug('PLEDG ' . __METHOD__ . ' default redirectUrl', compact('redirectUrl'));

        $pledgMethods = $this->paymentMethodProvider->getPledgMethods();

        foreach ($pledgMethods as $method) {
            $code = $method->getCode();
            $methodGatewayConfig = $this->pledgGatewayConfigReader->getConfigForPaymentMethodCode($code);

            $configUid = $parameters['merchantUid'];
            $this->logger->debug('PLEDG ' . __METHOD__ . ' ' . $methodGatewayConfig[PledgGatewayFactory::IDENTIFIER], compact('configUid', 'methodGatewayConfig'));
            if ($methodGatewayConfig[PledgGatewayFactory::IDENTIFIER] === $configUid) {
                $redirectUrl = $this->getPurchaseUrl($methodGatewayConfig) . '?signature=' . $token;

                $details = [
                    'redirect_parameters' => $parameters,
                    'redirect_url' => $redirectUrl,
                ];

                $request->getPayment()->setDetails($details);

                $this->logger->debug('PLEDG ' . __METHOD__, compact('redirectUrl'));
            }
        }

        throw new HttpRedirect($redirectUrl);
    }

    public function supports($request)
    {
        return $request instanceof RedirectUrlInterface;
    }

    protected function getPurchaseUrl(array $config): string
    {
        $url = $this->pledgGatewayConfigReader->getFrontUrl($config);
        return sprintf('%s/purchase', $url);
    }
}
