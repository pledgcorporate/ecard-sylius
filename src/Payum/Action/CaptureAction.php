<?php


namespace Pledg\SyliusPaymentPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrl;
use Pledg\SyliusPaymentPlugin\ValueObject\Merchant;
use Sylius\Component\Core\Model\PaymentInterface;

class CaptureAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Merchant::class;
    }

    /**
     * @param Capture|mixed $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $this->gateway->execute(RedirectUrl::fromCaptureAndMerchant($request, $this->api));
    }

    public function supports($request): bool
    {
        return $request instanceof Capture
            && $this->api instanceof Merchant
            && $request->getModel() instanceof PaymentInterface;
    }
}
