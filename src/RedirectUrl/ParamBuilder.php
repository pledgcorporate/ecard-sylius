<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\RedirectUrl;

use Payum\Core\Security\TokenInterface;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrlInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\MerchantInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Customer\Model\CustomerInterface;

class ParamBuilder implements ParamBuilderInterface
{
    /** @var MerchantInterface */
    protected $merchant;

    /** @var OrderInterface */
    protected $order;

    /** @var PaymentInterface */
    protected $payment;

    /** @var CustomerInterface */
    protected $customer;

    /** @var AddressInterface */
    protected $billingAddress;

    /** @var AddressInterface */
    protected $shippingAddress;

    /** @var TokenInterface */
    protected $token;

    public static function fromRedirectUrlRequest(RedirectUrlInterface $request): ParamBuilderInterface
    {
        $builder = new self();
        $builder->payment = $request->getModel();
        $builder->order = $builder->payment->getOrder();
        $builder->customer = $builder->order->getCustomer();
        $builder->billingAddress = $builder->order->getBillingAddress();
        $builder->shippingAddress = $builder->order->getShippingAddress();
        $builder->merchant = $request->getMerchant();

        if (null === $request->getToken()) {
            throw new \RuntimeException('You should have a token');
        }

        $builder->token = $request->getToken();

        return $builder;
    }

    public function build(): array
    {
        return [
            'merchantUid' => $this->merchant->getIdentifier(),
            'title' => $this->order->getNumber(),
            'reference' => 'pledg_' . $this->payment->getId(),
            'amountCents' => $this->order->getTotal(),
            'currency' => $this->payment->getCurrencyCode(),
            'firstName' => $this->billingAddress->getFirstName(),
            'lastName' => $this->billingAddress->getLastName(),
            'email' => $this->customer->getEmail(),
            'phoneNumber' => $this->billingAddress->getPhoneNumber(),
            'address' => $this->buildAddress($this->billingAddress),
            'shippingAddress' => $this->buildAddress($this->shippingAddress),
            'redirectUrl' => $this->token->getAfterUrl(),
            'cancelUrl' => $this->token->getAfterUrl(),
            'paymentNotificationUrl' => 'http://www.google.com',
        ];
    }

    private function buildAddress(AddressInterface $address): array
    {
        return [
            'street' => $address->getStreet(),
            'city' => $address->getCity(),
            'zipcode' => $address->getPostcode(),
            'country' => $address->getCountryCode(),
        ];
    }
}
