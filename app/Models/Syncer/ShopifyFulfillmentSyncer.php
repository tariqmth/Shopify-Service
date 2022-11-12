<?php

namespace App\Models\Syncer;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ExternalEntityNotFoundException;
use App\Exceptions\ImpossibleTaskException;
use App\Exceptions\InvalidDataException;
use App\Models\Fulfillment\ShopifyFulfillment;
use App\Models\Fulfillment\ShopifyFulfillmentRepository;
use App\Models\Job\SyncJobsHistory;
use App\Models\Order\ShopifyOrderRepository;
use App\Packages\ShopifySdkFactory;
use App\Queues\Jobs\SyncShopifyFulfillmentCompletionIn;
use App\Queues\Jobs\SyncShopifyFulfillmentIn;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ShopifyFulfillmentSyncer extends ShopifySyncer
{
    const MAX_DAILY_SYNCS = 20;

    protected $shopifySdkFactory;
    protected $syncerRepository;
    protected $shopifyOrderRepository;
    protected $shopifyFulfillmentRepository;
    protected $shopifyOrderSyncer;

    public function __construct(
        ShopifySdkFactory $shopifySdkFactory,
        SyncerRepository $syncerRepository,
        ShopifyOrderRepository $shopifyOrderRepository,
        ShopifyFulfillmentRepository $shopifyFulfillmentRepository
    ) {
        $this->shopifySdkFactory = $shopifySdkFactory;
        $this->syncerRepository = $syncerRepository;
        $this->shopifyOrderRepository = $shopifyOrderRepository;
        $this->shopifyFulfillmentRepository = $shopifyFulfillmentRepository;
    }

    public function syncOut(ShopifyFulfillment $shopifyFulfillment)
    {
        // todo
    }

    public function syncIn(ShopifyFulfillment $shopifyFulfillment, $shopifyFulfillmentData)
    {
        SyncShopifyFulfillmentIn::dispatch($shopifyFulfillment, $shopifyFulfillmentData)
            ->onConnection('database_sync')
            ->onQueue('fulfillment');
    }

    public function performSyncIn($shopifyFulfillmentId, $shopifyFulfillmentData)
    {
        $shopifyFulfillment = ShopifyFulfillment::findOrFail($shopifyFulfillmentId);
        $shopifyStore = $shopifyFulfillment->shopifyOrder->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);

        if (!isset($shopifyFulfillment->shopifyOrder->external_id)) {
            throw new ImpossibleTaskException(
                'Associated Shopify order does not exist or does not have an external ID.'
            );
        }

        if ($shopifyFulfillment->hasBeenSynced()) {
            $response = $shopifySdk->fulfillments->update(
                $shopifyFulfillment->external_id,
                $shopifyFulfillment->shopifyOrder->external_id,
                $shopifyFulfillmentData
            );
        } else {
            $response = $shopifySdk->fulfillments->create(
                $shopifyFulfillment->shopifyOrder->external_id,
                $shopifyFulfillmentData
            );
        }

        $this->limitApiCalls($shopifyStore, $response->creditLeft());
        $body = $response->parsedResponse();

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (ExternalEntityNotFoundException $e) {
            $this->handleEntityNotfound($shopifyFulfillment);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            if (isset($body->base) && strpos($body->base[0], 'already fulfilled') !== false) {
                // Sync out order to fetch latest fulfillments
                Log::notice('Fulfillment already exists in Shopify. Syncing out Shopify order again.');
                $shopifyOrderSyncer = resolve('App\Models\Syncer\ShopifyOrderSyncer');
                $shopifyOrderSyncer->syncOut($shopifyFulfillment->shopifyOrder);
                throw new ImpossibleTaskException($e);
            }
            // Sometimes Shopify throws errors for no reason - allow retry
            throw new \Exception($e);
        }

        $shopifyFulfillment->external_id = $body->id;
        $shopifyFulfillment->save();

        foreach ($body->line_items as $fulfillmentItemData) {
            $orderItem = $this->shopifyOrderRepository->getItem(
                $shopifyFulfillment->shopifyOrder,
                $fulfillmentItemData->id
            );
            $this->shopifyFulfillmentRepository
                ->getOrCreateItem($shopifyFulfillment->id, $orderItem->id, $fulfillmentItemData->quantity);
        }

        if ($shopifyFulfillment->shopify_voucher_product_id === null) {
            $this->syncInCompletion($shopifyFulfillment);
        }
    }

    public function syncInCompletion(ShopifyFulfillment $shopifyFulfillment)
    {
        if (!$shopifyFulfillment->hasBeenSynced()) {
            throw new \Exception('Cannot sync completion of Shopify fulfillment that has not been '
                . 'created in Shopify yet.');
        }

        SyncShopifyFulfillmentCompletionIn::dispatch($shopifyFulfillment)
            ->onConnection('database_sync')
            ->onQueue('fulfillment');
    }

    public function performSyncInCompletion($shopifyFulfillmentId)
    {
        $shopifyFulfillment = ShopifyFulfillment::findOrFail($shopifyFulfillmentId);
        $shopifyStore = $shopifyFulfillment->shopifyOrder->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);

        $response = $shopifySdk->fulfillments->complete(
            $shopifyFulfillment->external_id,
            $shopifyFulfillment->shopifyOrder->external_id
        );

        $this->limitApiCalls($shopifyStore, $response->creditLeft());

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (ExternalEntityNotFoundException $e) {
            $this->handleEntityNotfound($shopifyFulfillment);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            throw new ImpossibleTaskException($e);
        }

        $shopifyFulfillment->complete = true;
        $shopifyFulfillment->save();
    }

    private function handleEntityNotfound(ShopifyFulfillment $shopifyFulfillment)
    {
        $shopifyStore = $shopifyFulfillment->shopifyOrder->shopifyStore;
        Log::error('Shopify fulfillment ' . $shopifyFulfillment->external_id
            . ' not found in Shopify store '
            . $shopifyStore->subdomain
            . '. Deleting external ID from fulfillment '
            . $shopifyFulfillment->id
            . ' and resyncing.');
        $shopifyFulfillment->external_id = null;
        $shopifyFulfillment->save();
        $rexOrder = $shopifyFulfillment->shopifyOrder->rexOrder;

        if (isset($rexOrder)) {
            $previousSyncs = SyncJobsHistory::where('source', 'rex')
                ->where('queue', 'order')
                ->where('entity_id', $rexOrder->id)
                ->whereDate('created_at', Carbon::today())
                ->count();

            if ($previousSyncs >= self::MAX_DAILY_SYNCS) {
                Log::notice('Rex order ' . $rexOrder->id . ' cannot be resynced as it has already '
                    . 'been attempted too many times');
                return;
            }

            $rexOrderSyncer = $this->syncerRepository->getSyncer($rexOrder);
            $rexOrderSyncer->syncOut($rexOrder);
        }
    }
}
