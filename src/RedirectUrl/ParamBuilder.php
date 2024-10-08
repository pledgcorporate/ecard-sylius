<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\RedirectUrl;

use Composer\InstalledVersions;
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
use Sylius\Component\Inventory\Checker\AvailabilityChecker;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

class ParamBuilder implements ParamBuilderInterface
{
    public const PLEDG_PLUGIN_VERSION = '2.0.9';

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

        /** @var PaymentInterface $model */
        $model = $request->getModel();
        $builder->payment = $model;

        /** @var OrderInterface $order */
        $order = $builder->payment->getOrder();
        $builder->order = $order;

        /** @var CustomerInterface $customer */
        $customer = $builder->order->getCustomer();
        $builder->customer = $customer;

        /** @var AddressInterface $billingAddress */
        $billingAddress = $builder->order->getBillingAddress();
        $builder->billingAddress = $billingAddress;

        /** @var AddressInterface $shippingAddress */
        $shippingAddress = $builder->order->getShippingAddress();
        $builder->shippingAddress = $shippingAddress;

        $builder->merchant = $request->getMerchant();

        if (null === $request->getToken()) {
            throw new \RuntimeException('You should have a token');
        }

        $builder->token = $request->getToken();

        return $builder;
    }

    public function build(): array
    {
        $orderNumber = $this->order->getNumber();
        if (is_null($orderNumber)) {
            $orderNumber = '';
        }

        return [
            'merchantUid' => $this->merchant->getIdentifier(),
            'title' => $this->buildTitle($this->order->getItems()),
            'lang' => $this->order->getLocaleCode(),
            'reference' => (string) new Reference($orderNumber, $this->payment->getId()),
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
            $this->buildProductsMetadata($this->order->getItems()),
            $this->buildCustomerMetadata($this->order->getCustomer()),
            $this->buildPluginMetadata()
        );
    }

    /**
     * @param Collection|ShipmentInterface[] $shipments
     */
    private function buildShipmentMetadata(): array
    {
        /** @var Collection $shipments */
        $shipments = $this->order->getShipments();

        if ($shipments->isEmpty()) {
            return [];
        }

        /** @var array $names */
        $names = [];
        /** @var ShipmentInterface $shipment */
        foreach ($shipments as $shipment) {
            /** @var ShippingMethodInterface $method */
            $method = $shipment->getMethod();
            $names[] = $method;
        }

        $namesList = implode(', ', $names);

        return [
            'delivery_mode' => $namesList,
            'delivery_mode_reference' => $namesList,
        ];
    }

    /**
     * @param Collection|OrderItemInterface[] $orderItems
     */
    private function buildProductsMetadata(Collection $orderItems): array
    {
        $products = [];

        $shipmentMetadata = $this->buildShipmentMetadata();

        /** @var OrderItemInterface $item */
        foreach ($orderItems as $item) {
            $itemVariant = $item->getVariant();
            Assert::isInstanceOf($itemVariant, ProductVariantInterface::class);

            $availabilityChecker = new AvailabilityChecker(false);
            $isStockAvailable = $availabilityChecker->isStockAvailable($itemVariant);

            $itemProduct = $item->getProduct();
            $arrTaxons = $itemProduct->getTaxons();
            $category = '';
            $arrSubcategory = [];

            /** @var TaxonInterface $taxon */
            foreach ($arrTaxons as $taxon) {
                $taxonName = trim($taxon->getName());
                $arrSubcategory[] = $taxonName;
            }
            $category = $arrSubcategory[0];
            array_shift($arrSubcategory);

            $productMetadata = [
                'reference' => $itemVariant->getCode(),
                'name' => $item->getProductName(),
                'category' => $category,
                'sub_categories' => $arrSubcategory,
                'quantity' => $item->getQuantity(),
                'unit_amount_cents' => $item->getUnitPrice(),
                'type' => $itemVariant->isShippingRequired()
                    ? 'physical'
                    : 'virtual',
                'stock' => $isStockAvailable,
            ];

            // delivery data must be in each product details:
            $products[] = array_merge(
                $productMetadata,
                $shipmentMetadata
            );
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
                'number_of_purchases' => $customer->getOrders()->count(),
            ],
            'session' => [
                'customer_id' => $customer->getId(),
            ],
        ];
    }

    private function buildPluginMetadata(): array
    {
        $syliusVersion = InstalledVersions::getVersion('sylius/sylius');

        return [
            'plugin' => sprintf(
                'sylius%s-pledgbysofinco-plugin%s',
                $syliusVersion,
                self::PLEDG_PLUGIN_VERSION
            ),
        ];
    }
}
