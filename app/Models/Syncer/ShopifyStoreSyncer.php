<?php

namespace App\Models\Syncer;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ExternalEntityNotFoundException;
use App\Exceptions\ImpossibleTaskException;
use App\Exceptions\InvalidDataException;
use App\Models\Customer\RexCustomerRepository;
use App\Models\Customer\ShopifyCustomerRepository;
use App\Models\Location\ShopifyLocation;
use App\Models\Mapper\RexOrderMapperFromShopify;
use App\Models\Notification\ShopifyWebhook;
use App\Models\Order\RexOrderRepository;
use App\Models\Order\ShopifyOrderRepository;
use App\Models\Store\ShopifyStore;
use App\Packages\ShopifySdkFactory;
use App\Queues\Jobs\SyncShopifyStoreOut;
use App\Queues\Jobs\SyncShopifyWebhookIn;
use Illuminate\Support\Facades\Log;
use App\Models\Order\ShopifyOrder;

class ShopifyStoreSyncer extends ShopifySyncer
{
    protected $shopifySdkFactory;

    public function __construct(
        ShopifySdkFactory $shopifySdkFactory
    ) {
        $this->shopifySdkFactory = $shopifySdkFactory;
    }

    public function syncOut(ShopifyStore $shopifyStore, $shopifyStoreData = null)
    {
        if (!isset($shopifyStoreData)) {
            SyncShopifyStoreOut::dispatch($shopifyStore)
                ->onConnection('database_sync')
                ->onQueue('store');
        } else {
            $this->performSyncOut($shopifyStore->id, $shopifyStoreData);
        }
    }

    public function performSyncOut($shopifyStoreId, $shopifyStoreData = null)
    {
        $shopifyStore = ShopifyStore::findOrFail($shopifyStoreId);

        if (!isset($shopifyStoreData)) {
            $shopifyStoreData = $this->fetchShopifyStoreData($shopifyStore);
        }

        if ($shopifyStore->currency !== $shopifyStoreData->currency) {
            $shopifyStore->currency = $shopifyStoreData->currency;
            $shopifyStore->save();
        }

        $this->updatePrimaryLocation($shopifyStore, $shopifyStoreData);

        return $shopifyStore;
    }

    private function updatePrimaryLocation(ShopifyStore $shopifyStore, $shopifyStoreData)
    {
        $primaryLocation = ShopifyLocation
            ::where('shopify_store_id', $shopifyStore->id)
            ->where('is_primary', true)
            ->first();

        if (isset($primaryLocation)) {
            if ($primaryLocation->external_id === $shopifyStoreData->primary_location_id) {
                return;
            } else {
                $primaryLocation->is_primary = false;
                $primaryLocation->save();
            }
        }

        $newLocation = ShopifyLocation
            ::where('shopify_store_id', $shopifyStore->id)
            ->where('external_id', $shopifyStoreData->primary_location_id)
            ->first();

        if (!isset($newLocation)) {
            $newLocation = new ShopifyLocation;
            $newLocation->shopify_store_id = $shopifyStore->id;
            $newLocation->external_id = $shopifyStoreData->primary_location_id;
        }

        $newLocation->is_primary = true;
        $newLocation->save();
    }

    private function fetchShopifyStoreData(ShopifyStore $shopifyStore)
    {
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);
        $response = $shopifySdk->shops->read();
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

        return $response->parsedResponse();
    }
}
