<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\RedirectUrl;

use Doctrine\Common\Collections\Collection;
use Payum\Core\Security\TokenInterface;
use Pledg\SyliusPaymentPlugin\Calculator\TotalWithoutFeesInterface;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrlInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\MerchantInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Reference;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Customer\Model\CustomerInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

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

    /** @var RouterInterface */
    protected $router;

    /** @var TotalWithoutFeesInterface */
    protected $totalWithoutFees;

    public static function fromRedirectUrlRequest(
        RedirectUrlInterface $request,
        TotalWithoutFeesInterface $totalWithoutFees,
        RouterInterface $router
    ): ParamBuilderInterface {
        $builder = new self();
        $builder->router = $router;
        $builder->totalWithoutFees = $totalWithoutFees;
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
            'title' => $this->buildTitle($this->order->getItems()),
            'lang' => $this->order->getLocaleCode(),
            'reference' => (string) new Reference($this->order->getId(), $this->payment->getId()),
            'amountCents' => $this->totalWithoutFees->calculate($this->order),
            'currency' => $this->payment->getCurrencyCode(),
            'firstName' => $this->billingAddress->getFirstName(),
            'lastName' => $this->billingAddress->getLastName(),
            'email' => $this->customer->getEmail(),
            'phoneNumber' => $this->billingAddress->getPhoneNumber(),
            'address' => $this->buildAddress($this->billingAddress),
            'shippingAddress' => $this->buildAddress($this->shippingAddress),
            'countryCode' => $this->billingAddress->getCountryCode(),
            'metadata' => $this->buildMetadata(),
            'redirectUrl' => $this->token->getAfterUrl(),
            'cancelUrl' => $this->token->getAfterUrl(),
            'paymentNotificationUrl' => $this->router->generate(
                'pledg_sylius_payment_plugin_webhook_notification',
                [],
                RouterInterface::ABSOLUTE_URL
            ),
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

    /**
     * @param Collection|OrderItemInterface[] $orderItems
     */
    private function buildTitle(Collection $orderItems): string
    {
        $names = [];
        /** @var OrderItemInterface $item */
        foreach ($orderItems as $item) {
            if (null !== $item->getProductName()) {
                $names[] = $item->getProductName();
            } elseif (null !== $item->getVariantName()) {
                $names[] = $item->getVariantName();
            }
        }

        return implode(', ', $names);
    }

    private function buildMetadata(): array
    {
        return array_merge(
            $this->buildShipmentMetadata($this->order->getShipments()),
            $this->buildProductsMetadata($this->order->getItems()),
            $this->buildCustomerMetadata($this->order->getCustomer()),
            ['plugin' => 'sylius-pledg-plugin0.1.*'],
        );
    }

    /**
     * @param Collection|ShipmentInterface[] $shipments
     */
    private function buildShipmentMetadata(Collection $shipments): array
    {
        if ($shipments->isEmpty()) {
            return [];
        }

        $names = [];
        /** @var ShipmentInterface $shipment */
        foreach ($shipments as $shipment) {
            /** @var ShippingMethodInterface $method */
            $method = $shipment->getMethod();
            $names[] = $method;
        }

        return [
            'delivery_label' => implode(', ', $names),
        ];
    }

    /**
     * @param Collection|OrderItemInterface[] $orderItems
     */
    private function buildProductsMetadata(Collection $orderItems): array
    {
        $products = [];

        /** @var OrderItemInterface $item */
        foreach ($orderItems as $item) {
            Assert::isInstanceOf($item->getVariant(), ProductVariantInterface::class);

            $products[] = [
                'reference' => $item->getVariant()->getCode(),
                'name' => $item->getVariantName(),
                'quantity' => $item->getQuantity(),
                'unit_amount_cents' => $item->getUnitPrice(),
                'type' => $item->getVariant()->isShippingRequired()
                    ? 'physical'
                    : 'virtual',
            ];
        }

        return ['products' => $products];
    }

    private function buildCustomerMetadata(?CustomerInterface $customer = null): array
    {
        if (!$customer instanceof Customer) {
            return [];
        }

        return [
            'account' => [
                'creation_date' => $customer->getCreatedAt() instanceof \DateTimeInterface
                    ? $customer->getCreatedAt()->format('Y-m-d')
                    : null,
            ],
            'session' => [
                'customer_id' => $customer->getId(),
            ],
        ];
    }
}
