<?php


namespace Pledg\SyliusPaymentPlugin\RedirectUrl;


use Payum\Core\Security\TokenInterface;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrl;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrlInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Merchant;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Customer\Model\CustomerInterface;

class ParamBuilder implements ParamBuilderInterface
{
    /** @var Merchant */
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
        $builder->token = $request->getToken();

        return $builder;
    }

    public function build(): array
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
}
