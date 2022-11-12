<?php

namespace App\Models\Syncer;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ExternalEntityNotFoundException;
use App\Exceptions\ImpossibleTaskException;
use App\Exceptions\InvalidDataException;
use App\Models\Voucher\ShopifyVoucher;
use App\Models\Voucher\ShopifyVoucherAdjustment;
use App\Packages\ShopifySdkFactory;
use App\Queues\Jobs\SyncShopifyVoucherAdjustmentIn;
use App\Queues\Jobs\SyncShopifyVoucherIn;

class ShopifyVoucherSyncer extends ShopifySyncer
{
    protected $shopifySdkFactory;

    public function __construct
    (
        ShopifySdkFactory $shopifySdkFactory
    ) {
        $this->shopifySdkFactory = $shopifySdkFactory;
    }

    public function syncIn(ShopifyVoucher $shopifyVoucher, $shopifyVoucherData)
    {
        SyncShopifyVoucherIn::dispatch($shopifyVoucher, $shopifyVoucherData)
            ->onConnection('database_sync')
            ->onQueue('voucher');
    }

    public function syncInAdjustment(ShopifyVoucherAdjustment $shopifyVoucherAdjustment, $shopifyVoucherAdjustmentData)
    {
        SyncShopifyVoucherAdjustmentIn::dispatch($shopifyVoucherAdjustment, $shopifyVoucherAdjustmentData)
            ->onConnection('database_sync')
            ->onQueue('voucher');
    }

    public function performSyncIn($shopifyVoucherId, $shopifyVoucherData)
    {
        $shopifyVoucher = ShopifyVoucher::findOrFail($shopifyVoucherId);

        $shopifyStore = $shopifyVoucher->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore,true);

        if ($shopifyVoucher->hasBeenSynced()) {
            throw new ImpossibleTaskException('Cannot update Shopify gift card that has already been synced.');
        } else {
            $response = $shopifySdk->gift_cards->create($shopifyVoucherData);
        }

        $this->limitApiCalls($shopifyStore, $response->creditLeft());

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (ExternalEntityNotFoundException $e) {
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            throw new ImpossibleTaskException($e);
        }

        $body = $response->parsedResponse();
        $shopifyVoucher->external_id = $body->id;
        $shopifyVoucher->save();

        return $shopifyVoucher;
    }

    public function performSyncInAdjustment($shopifyVoucherAdjustmentId, $shopifyVoucherAdjustmentData)
    {
        $shopifyVoucherAdjustment = ShopifyVoucherAdjustment::findOrFail($shopifyVoucherAdjustmentId);
        $shopifyVoucher = $shopifyVoucherAdjustment->shopifyVoucher;
        $shopifyStore = $shopifyVoucher->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore,true);

        if ($shopifyVoucherAdjustment->hasBeenSynced()) {
            throw new ImpossibleTaskException('Shopify gift card adjustment has already been synced.');
        }

        if (!$shopifyVoucher->hasBeenSynced()) {
            throw new \Exception('Cannot adjust Shopify gift card that has not been synced to Shopify.');
        }

        $response = $shopifySdk->gift_cards->adjust(
            $shopifyVoucher->external_id,
            $shopifyVoucherAdjustmentData
        );

        $this->limitApiCalls($shopifyStore, $response->creditLeft());

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (ExternalEntityNotFoundException $e) {
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            throw new ImpossibleTaskException($e);
        }

        $body = $response->parsedResponse();
        $shopifyVoucherAdjustment->external_id = $body->id;
        $shopifyVoucherAdjustment->save();

        return $shopifyVoucherAdjustment;
    }
}