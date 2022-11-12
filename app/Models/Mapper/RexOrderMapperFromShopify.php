<?php

namespace App\Models\Mapper;

use App\Models\Order\RexOrder;
use App\Models\OrderFields\ShopifyOrderAttributeRepository;
use App\Models\Product\ShopifyProduct;
use App\Models\Product\ShopifyProductVariant;
use RetailExpress\SkyLink\Sdk\Customers\BillingContact as RexBillingContactData;
use RetailExpress\SkyLink\Sdk\Customers\ShippingContact as RexShippingContactData;
use RetailExpress\SkyLink\Sdk\Outlets\OutletId;
use RetailExpress\SkyLink\Sdk\Sales\Orders\Item as RexItemData;
use RetailExpress\SkyLink\Sdk\Sales\Orders\ItemFulfillmentMethod;
use RetailExpress\SkyLink\Sdk\Sales\Orders\Order as RexOrderData;
use DateTimeImmutable;
use RetailExpress\SkyLink\Sdk\Sales\Orders\ShippingCharge as RexShippingChargeData;
use RetailExpress\SkyLink\Sdk\Sales\Orders\Status as RexOrderStatusData;
use RetailExpress\SkyLink\Sdk\Customers\CustomerId as RexCustomerIdData;
use App\Models\Mapper\Helper\SingleTaxRateFromMultiple;
use ValueObjects\StringLiteral\StringLiteral;

class RexOrderMapperFromShopify extends Mapper
{
    use SingleTaxRateFromMultiple;

    const PRIVATE_COMMENTS_FIELD_NAME = 'private_comments';
    const PUBLIC_COMMENTS_FIELD_NAME = 'public_comments';

    protected $rexAddressMapperFromShopify;
    protected $shopifyOrderAttributeRepository;

    public function __construct(
        RexAddressMapperFromShopify $rexAddressMapperFromShopify,
        ShopifyOrderAttributeRepository $shopifyOrderAttributeRepository
    ) {
        $this->rexAddressMapperFromShopify = $rexAddressMapperFromShopify;
        $this->shopifyOrderAttributeRepository = $shopifyOrderAttributeRepository;
    }

    public function getMappedData(RexOrder $rexOrder, $shopifyOrderData)
    {
        $shopifyStoreId = $rexOrder->rexSalesChannel->shopifyStore->id;

        if (isset($rexOrder->rexCustomer) && $rexOrder->rexCustomer->hasBeenSynced()) {
            $rexOrderData = RexOrderData::forCustomerWithId(
                $this->getCustomerId($rexOrder),
                $this->getPlacedAt($shopifyOrderData),
                $this->getStatus($shopifyOrderData),
                $this->getBillingContact($shopifyOrderData),
                $this->getShippingContact($shopifyOrderData),
                $this->getShippingCharge($shopifyOrderData),
                $this->getTaxFree($shopifyOrderData)
            );
        } else {
            $rexOrderData = new RexOrderData(
                $this->getPlacedAt($shopifyOrderData),
                $this->getStatus($shopifyOrderData),
                $this->getBillingContact($shopifyOrderData),
                $this->getShippingContact($shopifyOrderData),
                $this->getShippingCharge($shopifyOrderData),
                $this->getTaxFree($shopifyOrderData)
            );
        }

        $rexOrderData = $rexOrderData->withExternalOrderId($this->getExternalOrderId($shopifyOrderData));
        $rexOrderData = $rexOrderData->withFreightType($this->getFreightType($shopifyOrderData));
        $rexOrderData = $rexOrderData->withNewsletterSubscription($this->getNewsletterSubscription($shopifyOrderData));
        $rexOrderData = $this->getOrderWithPrivateComments($shopifyOrderData, $rexOrderData, $shopifyStoreId);
        $rexOrderData = $this->getOrderWithPublicComments($shopifyOrderData, $rexOrderData, $shopifyStoreId);
        $rexOrderData = $this->getOrderWithFulfillmentMethod($shopifyOrderData, $rexOrderData);

        return $rexOrderData;
    }

    protected function getFulfillmentMethod($shopifyOrderData)
    {
        foreach ($shopifyOrderData->note_attributes as $noteAttribute) {
            if ($noteAttribute->name === 'cnc-fulfilment-method') {
                return $noteAttribute->value;
            }
        }

        return 'home';
    }

    protected function getOutletId($shopifyOrderData)
    {
        foreach ($shopifyOrderData->note_attributes as $noteAttribute) {
            if ($noteAttribute->name === 'cnc-outlet') {
                return $noteAttribute->value;
            }
        }
    }

    protected function getCustomerId($rexOrder)
    {
        return RexCustomerIdData::fromNative($rexOrder->rexCustomer->external_id);
    }

    protected function getPlacedAt($shopifyOrderData)
    {
        return new DateTimeImmutable($shopifyOrderData->created_at);
    }

    protected function getStatus($shopifyOrderData)
    {
        if ($shopifyOrderData->cancelled_at !== null) {
            throw new \Exception('Cannot map cancelled order.');
        }

        $pendingPaymentStatuses = ['pending', 'authorized', 'partially_paid'];
        $paidStatuses = ['paid', 'partially_refunded'];

        if (in_array($shopifyOrderData->financial_status, $paidStatuses)
            && $shopifyOrderData->closed_at !== null
            && $shopifyOrderData->fulfillment_status === 'fulfilled'
        ) {
            $status = RexOrderStatusData::COMPLETE;
        } elseif (in_array($shopifyOrderData->financial_status, $paidStatuses)) {
            $status = RexOrderStatusData::PROCESSING;
        } elseif (in_array($shopifyOrderData->financial_status, $pendingPaymentStatuses)) {
            $status = RexOrderStatusData::PENDING_PAYMENT;
        } else {
            throw new \Exception('Could not map order status.');
        }

        return RexOrderStatusData::get($status);
    }

    protected function getBillingContact($shopifyOrderData)
    {
        if (isset($shopifyOrderData->email) && !empty($shopifyOrderData->email)) {
            $email = $shopifyOrderData->email;
        } else {
            $email = 'skystoreadmin@retailexpress.com.au';
        }

        if (isset($shopifyOrderData->billing_address)) {
            return $this->rexAddressMapperFromShopify->getMappedBillingContact(
                $shopifyOrderData->billing_address,
                $email
            );
        } else {
            return RexBillingContactData::fromNative('', '', $email);
        }
    }

    protected function getShippingContact($shopifyOrderData)
    {
        if (isset($shopifyOrderData->shipping_address)) {
            return $this->rexAddressMapperFromShopify->getMappedShippingContact($shopifyOrderData->shipping_address);
        } else {
            return RexShippingContactData::fromNative();
        }
    }

    protected function getShippingCharge($shopifyOrderData)
    {
        $shippingLines = $shopifyOrderData->shipping_lines;
        $taxRates = [];
        $price = 0;

        foreach ($shippingLines as $shippingLine) {
            $shippingLinePrice = (float) $shippingLine->price;
            if (isset($shippingLine->discount_allocations)) {
                $discount = 0;
                foreach ($shippingLine->discount_allocations as $discountAllocation) {
                    $discount += $discountAllocation->amount;
                }
                $shippingLinePrice -= $discount;
            }
            $price += $shippingLinePrice;
            foreach ($shippingLine->tax_lines as $taxLine) {
                $taxRates[] = $taxLine->rate;
            }
        }

        $taxRate = $this->getTaxRate($taxRates);

        return RexShippingChargeData::fromNative($price, $taxRate);
    }

    protected function getTaxFree($shopifyOrderData)
    {
        return (float) $shopifyOrderData->total_tax === (float) 0;
    }
    protected function getNewsletterSubscription($shopifyOrderData)
    {
        return $shopifyOrderData->buyer_accepts_marketing;
    }

    protected function getExternalOrderId($shopifyOrderData)
    {
        return preg_replace('/#/', '', $shopifyOrderData->name, 1);
    }

    protected function getFreightType($shopifyOrderData)
    {
        $shippingTitles = array_map(function($shippingLine) {
            return $shippingLine->title;
        }, $shopifyOrderData->shipping_lines);

        $freightType = implode(', ', $shippingTitles);

        return $freightType;
    }

    protected function getOrderWithPrivateComments($shopifyOrderData, $rexOrderData, $shopifyStoreId)
    {
        $comments = [];

        $mappings = $this->shopifyOrderAttributeRepository->getMappings($shopifyStoreId);

        foreach ($shopifyOrderData->note_attributes as $note) {
            foreach ($mappings as $mapping) {
                if ($mapping->shopify_order_attribute === $note->name
                    && $mapping->rex_order_field === self::PRIVATE_COMMENTS_FIELD_NAME
                ) {
                    $comments[] = $note->name . ': ' . $note->value;
                }
            }
        }

        if (isset($shopifyOrderData->presentment_currency)
            && $shopifyOrderData->currency !== $shopifyOrderData->presentment_currency
        ) {
            $foreignCurrencyComment = 'Payments on this order will be converted from '
                . $shopifyOrderData->presentment_currency . ' to ' . $shopifyOrderData->currency . '. '
                . 'Changes to the exchange rate during checkout may cause the total paid to differ slightly from '
                . 'the order total.';
            $comments[] = $foreignCurrencyComment;
        }

        if (count($comments)) {
            $commentsString = implode('; ', $comments);
            $rexOrderData = $rexOrderData->withPrivateComments(StringLiteral::fromNative($commentsString));
        }

        return $rexOrderData;
    }

    protected function getOrderWithPublicComments($shopifyOrderData, $rexOrderData, $shopifyStoreId)
    {
        $comments = [];

        $mappings = $this->shopifyOrderAttributeRepository->getMappings($shopifyStoreId);

        foreach ($shopifyOrderData->note_attributes as $note) {
            foreach ($mappings as $mapping) {
                if ($mapping->shopify_order_attribute === $note->name
                    && $mapping->rex_order_field === self::PUBLIC_COMMENTS_FIELD_NAME
                ) {
                    $comments[] = $note->name . ': ' . $note->value;
                }
            }
        }

        if (count($comments)) {
            $commentsString = implode('; ', $comments);
            $rexOrderData = $rexOrderData->withPublicComments(StringLiteral::fromNative($commentsString));
        }

        return $rexOrderData;
    }

    protected function getOrderWithFulfillmentMethod($shopifyOrderData, $rexOrderData)
    {
        $fulfillmentMethodData = ItemFulfillmentMethod::fromNative($this->getFulfillmentMethod($shopifyOrderData));
        $rexOrderData = $rexOrderData->withFulfillmentMethodForAllItems($fulfillmentMethodData);

        if (!empty($this->getOutletId($shopifyOrderData))) 
        {
            $outletIdData = OutletId::fromNative($this->getOutletId($shopifyOrderData));
            $rexOrderData = $rexOrderData->fulfillFromOutletId(OutletId::fromNative($outletIdData));
        }

        return $rexOrderData;
    }
}
