<?php


namespace Pledg\SyliusPaymentPlugin\Payum\Request;

use Payum\Core\Request\Capture;
use Payum\Core\Request\Generic;
use Pledg\SyliusPaymentPlugin\ValueObject\Merchant;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Customer\Model\CustomerInterface;

class RedirectUrl extends Generic
{
    /** @var Merchant */
    private $merchant;

    /** @var OrderInterface */
    private $order;

    /** @var PaymentInterface */
    private $payment;

    /** @var CustomerInterface */
    private $customer;

    /** @var AddressInterface */
    private $billingAddress;

    /** @var AddressInterface */
    private $shippingAddress;

    public function buildParameters(): array
    {
        return [
            'merchantUid' => $this->merchant->getIdentifier(),
            'title' => $this->order->getNumber(),
            'subtitle' => 'test',
            'reference' => $this->payment->getId(),
            'amountCents' => $this->order->getTotal(),
            'currency' => 'EUR', //$this->payment->getCurrencyCode(),
            'firstName' => $this->billingAddress->getFirstName(),
            'lastName' => $this->billingAddress->getLastName(),
            'email' => 'contact@fabiensalles.com', // $this->customer->getEmail(),
            'phoneNumber' => $this->customer->getPhoneNumber(),
            'address' => $this->buildAddress($this->billingAddress),
            'shippingAddress' => $this->buildAddress($this->shippingAddress),
            'redirectUrl' => $this->token->getAfterUrl(),
            'cancelUrl' => 'http://www.google.com',
            'paymentNotificationUrl' => 'http://www.google.com',
        ];
    }

    private function buildAddress(AddressInterface $address): array
    {
        return [
            'street' => $address->getStreet(),
            'city' => $address->getCity(),
            'zipcode' => $address->getCity(),
            'country' => $address->getCountryCode(),
        ];
    }

    public static function fromCaptureAndMerchant(Capture $capture, Merchant $merchant): self
    {
        /** @var PaymentInterface $payment */
        $payment = $capture->getModel();
        /** @var OrderInterface $order */
        $order = $payment->getOrder();
        /** @var CustomerInterface $customer */
        $customer = $order->getCustomer();
        /** @var AddressInterface $billingAddress */
        $billingAddress = $order->getBillingAddress();
        /** @var AddressInterface $shippingAddress */
        $shippingAddress = $order->getShippingAddress();

        $request = new self($capture->getToken());
        $request->setFirstModel($capture->getFirstModel());
        $request->setModel($payment);
        $request->merchant = $merchant;
        $request->payment = $payment;
        $request->order = $order;
        $request->customer = $customer;
        $request->billingAddress = $billingAddress;
        $request->shippingAddress = $shippingAddress;

        return $request;
    }
}
