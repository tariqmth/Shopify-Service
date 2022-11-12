<?php

namespace App\Models\Notification;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Attribute\AttributeRepository;
use App\Models\Customer\RexCustomerRepository;
use App\Models\License\AddonLicense;
use App\Models\Order\RexOrderRepository;
use App\Models\Store\RexSalesChannel;
use App\Models\Syncer\RexAttributeOptionSyncer;
use App\Models\Syncer\RexCustomerSyncer;
use App\Models\Syncer\RexOrderSyncer;
use App\Models\Syncer\RexOutletSyncer;
use App\Models\Syncer\RexVoucherSyncer;
use App\Models\Syncer\ShopifyProductSyncer;
use App\Models\Voucher\RexVoucherRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Product\RexProductRepository;
use App\Models\Syncer\RexProductSyncer;
use App\Models\Client\Client;
use Illuminate\Support\Facades\Log;

class RexEDSNotificationHandler
{
    private $productSyncer;
    private $productRepository;
    private $attributeOptionSyncer;
    private $attributeRepository;
    private $rexCustomerRepository;
    private $rexCustomerSyncer;
    private $rexOrderRepository;
    private $rexOrderSyncer;
    private $shopifyProductSyncer;
    private $rexVoucherRepository;
    private $rexVoucherSyncer;
    private $rexOutletSyncer;

    public function __construct(
        RexProductSyncer $productSyncer,
        RexProductRepository $productRepository,
        RexAttributeOptionSyncer $attributeOptionSyncer,
        AttributeRepository $attributeRepository,
        RexCustomerRepository $rexCustomerRepository,
        RexCustomerSyncer $rexCustomerSyncer,
        RexOrderRepository $rexOrderRepository,
        RexOrderSyncer $rexOrderSyncer,
        ShopifyProductSyncer $shopifyProductSyncer,
        RexVoucherRepository $rexVoucherRepository,
        RexVoucherSyncer $rexVoucherSyncer,
        RexOutletSyncer $rexOutletSyncer
    ) {
        $this->productSyncer = $productSyncer;
        $this->productRepository = $productRepository;
        $this->attributeOptionSyncer = $attributeOptionSyncer;
        $this->attributeRepository = $attributeRepository;
        $this->rexCustomerRepository = $rexCustomerRepository;
        $this->rexCustomerSyncer = $rexCustomerSyncer;
        $this->rexOrderRepository = $rexOrderRepository;
        $this->rexOrderSyncer = $rexOrderSyncer;
        $this->shopifyProductSyncer = $shopifyProductSyncer;
        $this->rexVoucherRepository = $rexVoucherRepository;
        $this->rexVoucherSyncer = $rexVoucherSyncer;
        $this->rexOutletSyncer = $rexOutletSyncer;
    }

    public function process($clientExternalId, $entity, $rexSalesChannelExternalId = null)
    {
        $salesChannelForLog = $rexSalesChannelExternalId ?: 'unknown';
        Log::debug('Processing EDS notification for client ' . $clientExternalId
            . ' sales channel ' . $salesChannelForLog . '.', (array) $entity);

        try {
            $client = Client::where('external_id', $clientExternalId)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new ImpossibleTaskException('Client could not be found.');
        }

        $rexSalesChannels = $client->rexSalesChannels()->where('external_id', $rexSalesChannelExternalId)->get();

        if ($rexSalesChannels->isEmpty() || !isset($rexSalesChannelExternalId)) {
            $rexSalesChannels = $client->rexSalesChannels;
        }

        if ($entity->Type == 'Product') {
            $this->syncProducts($rexSalesChannels, $entity->List);
        } elseif ($entity->Type == 'Colour') {
            $this->syncAttributeOption($client, 'colour', $entity->List);
        } elseif ($entity->Type == 'Size') {
            $this->syncAttributeOption($client, 'size', $entity->List);
        } elseif ($entity->Type == 'ProductType') {
            $this->syncAttributeOption($client, 'product_type', $entity->List);
        } elseif ($entity->Type == 'Brand') {
            $this->syncAttributeOption($client, 'brand', $entity->List);
        } elseif ($entity->Type == 'SalesChannel') {
            $this->syncAllProducts($rexSalesChannels, $entity->List);
        } elseif ($entity->Type == 'Customer') {
            $this->syncCustomers($rexSalesChannels, $entity->List);
        } elseif ($entity->Type == 'BulkCustomer') {
            $this->syncAllCustomers($rexSalesChannels, $entity->List);
        } elseif ($entity->Type == 'Order') {
            $this->syncOrders($rexSalesChannels, $entity->List);
        } elseif ($entity->Type == 'Voucher') {
            $this->syncVouchers($rexSalesChannels, $entity->List);
        } elseif ($entity->Type == 'BulkVoucher') {
            $this->syncAllVouchers($rexSalesChannels, $entity->List);
        } elseif ($entity->Type == 'Outlet') {
            $this->syncOutlets($rexSalesChannels);
        }
    }

    protected function syncProducts($rexSalesChannels, array $productIds)
    {
        foreach ($productIds as $productId) {
            foreach ($rexSalesChannels as $salesChannel) {
                $product = $this->productRepository->create($salesChannel, $productId);
                $shopifyStore = $salesChannel->shopifyStore;
                if (isset($shopifyStore) && $shopifyStore->enabled) {
                    $this->productSyncer->syncOut($product);
                }
            }
        }
    }

    protected function syncAllProducts($rexSalesChannels, array $requestedSalesChannelIds)
    {
        foreach ($rexSalesChannels as $rexSalesChannel) {
            if (!in_array($rexSalesChannel->external_id, $requestedSalesChannelIds)) {
                continue;
            }
            $shopifyStore = $rexSalesChannel->shopifyStore;
            if (isset($shopifyStore) && $shopifyStore->enabled) {
                $this->productSyncer->syncAllOut($rexSalesChannel);
            }
        }
    }

    protected function syncAttributeOption(Client $client, $attributeName, array $attributeOptionIds)
    {
        $attribute = $this->attributeRepository->createAttribute($client->id, $attributeName);
        foreach ($attributeOptionIds as $attributeOptionId) {
            $attributeOption = $this->attributeRepository->createAttributeOption($attribute->id, $attributeOptionId);
            $this->attributeOptionSyncer->syncOut($attributeOption);
        }
    }

    protected function syncCustomers($rexSalesChannels, array $customerIds)
    {
        foreach ($customerIds as $customerId) {
            foreach ($rexSalesChannels as $rexSalesChannel) {
                $rexCustomer = $this->rexCustomerRepository->getOrCreate($rexSalesChannel->id, $customerId);
                $this->rexCustomerSyncer->syncOut($rexCustomer);
            }
        }
    }

    protected function syncAllCustomers($rexSalesChannels, array $requestedSalesChannelIds)
    {
        foreach ($rexSalesChannels as $rexSalesChannel) {
            if (!in_array($rexSalesChannel->external_id, $requestedSalesChannelIds)) {
                continue;
            }
            $shopifyStore = $rexSalesChannel->shopifyStore;
            if (isset($shopifyStore) && $shopifyStore->enabled) {
                $this->rexCustomerSyncer->syncAllOut($rexSalesChannel);
            }
        }
    }

    protected function syncOrders($rexSalesChannels, array $orderIds)
    {
        foreach ($orderIds as $orderId) {
            foreach ($rexSalesChannels as $rexSalesChannel) {
                $rexOrder = $this->rexOrderRepository->get($rexSalesChannel->id, $orderId);
                if (isset($rexOrder)) {
                    $this->rexOrderSyncer->syncOut($rexOrder);
                }
            }
        }
    }

    protected function syncVouchers($rexSalesChannels, array $voucherIds)
    {
        foreach ($voucherIds as $voucherId) {
            foreach ($rexSalesChannels as $rexSalesChannel) {
                $rexVoucher = $this->rexVoucherRepository->get($rexSalesChannel->id, $voucherId);
                if (!isset($rexVoucher)) {
                    if (!$this->isSalesChannelLicensed($rexSalesChannel, 'gift_vouchers')) {
                        throw new ImpossibleTaskException('Client is not licensed to create new gift vouchers.');
                    }
                    $rexVoucher = $this->rexVoucherRepository->create($rexSalesChannel->id, $voucherId);
                }
                if (isset($voucherId)) {
                    $this->rexVoucherSyncer->syncOut($rexVoucher);
                }
            }
        }
    }

    protected function syncAllVouchers($rexSalesChannels, array $requestedSalesChannelIds)
    {
        foreach ($rexSalesChannels as $rexSalesChannel) {
            if (!in_array($rexSalesChannel->external_id, $requestedSalesChannelIds)) {
                Log::warning('Sales channel requested did not match header.');
                continue;
            }
            if (!$this->isSalesChannelLicensed($rexSalesChannel, 'gift_vouchers')) {
                Log::warning('Sales channel ' . $rexSalesChannel->id . ' is not licensed for voucher bulk sync.');
                continue;
            }
            $shopifyStore = $rexSalesChannel->shopifyStore;
            if (isset($shopifyStore) && $shopifyStore->enabled) {
                $this->rexVoucherSyncer->syncAllOut($rexSalesChannel);
            }
        }
    }

    protected function syncOutlets($rexSalesChannels)
    {
        foreach ($rexSalesChannels as $rexSalesChannel) {
            $this->rexOutletSyncer->syncAllOut($rexSalesChannel);
        }
    }

    private function isSalesChannelLicensed($rexSalesChannel, $licenseName)
    {
        return $rexSalesChannel->client->addonLicenses()->where('name', $licenseName)->first() ? true : false;
    }
}
