<?php

namespace App\Models\Syncer;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ExternalEntityNotFoundException;
use App\Exceptions\ImpossibleTaskException;
use App\Exceptions\InvalidDataException;
use App\Models\Inventory\ShopifyInventoryItem;
use App\Models\Job\SyncJobsHistory;
use App\Models\Product\RexProductRepository;
use App\Packages\ShopifySdkFactory;
use App\Queues\Jobs\SyncShopifyInventoryItemIn;
use App\Queues\Jobs\SyncShopifyInventoryLevelIn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShopifyInventoryItemSyncer extends ShopifySyncer
{
    const MAX_DAILY_SYNCS = 20;

    protected $shopifySdkFactory;
    protected $rexProductRepository;
    protected $syncerRepository;

    public function __construct(
        ShopifySdkFactory $shopifySdkFactory,
        RexProductRepository $rexProductRepository,
        SyncerRepository $syncerRepository
    ) {
        $this->shopifySdkFactory = $shopifySdkFactory;
        $this->rexProductRepository = $rexProductRepository;
        $this->syncerRepository = $syncerRepository;
    }

    public function syncOut(ShopifyInventoryItem $shopifyInventoryItem)
    {
        // todo
    }

    public function syncIn(ShopifyInventoryItem $shopifyInventoryItem, $inventoryItemData)
    {
        SyncShopifyInventoryItemIn::dispatch($shopifyInventoryItem, $inventoryItemData)
            ->onConnection('database_inventory_sync')
            ->onQueue('product_inventory');
    }

    public function performSyncIn($shopifyInventoryItemId, $inventoryItemData)
    {
        $shopifyInventoryItem = ShopifyInventoryItem::findOrFail($shopifyInventoryItemId);
        $shopifyStore = $shopifyInventoryItem->shopifyProductVariant->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);

        $response = $shopifySdk->inventory_items->update(
            $shopifyInventoryItem->external_id,
            $inventoryItemData
        );

        $this->limitApiCalls($shopifyStore, $response->creditLeft());
        $body = $response->parsedResponse();

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (ExternalEntityNotFoundException $e) {
            $this->handleEntityNotfound($shopifyInventoryItem);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            throw new ImpossibleTaskException($e);
        }
    }

    public function syncInLevel(ShopifyInventoryItem $shopifyInventoryItem, $inventoryLevelData)
    {
        SyncShopifyInventoryLevelIn::dispatch($shopifyInventoryItem, $inventoryLevelData)
            ->onConnection('database_inventory_sync')
            ->onQueue('product_inventory');
    }

    public function performSyncInLevel($shopifyInventoryItemId, $inventoryLevelData)
    {
        $shopifyInventoryItem = ShopifyInventoryItem::findOrFail($shopifyInventoryItemId);
        $shopifyStore = $shopifyInventoryItem->shopifyProductVariant->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);

        $response = $shopifySdk->inventory_levels->set(
            $inventoryLevelData
        );

        $this->limitApiCalls($shopifyStore, $response->creditLeft());

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            throw new ImpossibleTaskException($e);
        }
    }

    private function handleEntityNotfound(ShopifyInventoryItem $shopifyInventoryItem)
    {
        $shopifyStore = $shopifyInventoryItem->shopifyProductVariant->shopifyStore;
        $shopifyProduct = $shopifyInventoryItem->shopifyProductVariant->shopifyProduct;
        $rexProduct = $this->rexProductRepository->getFirstForShopifyProduct($shopifyProduct);
        Log::error('Shopify inventory item ' . $shopifyInventoryItem->external_id
            . ' not found in Shopify store '
            . $shopifyStore->subdomain
            . '. Deleting external ID and resyncing Rex product if available.');
        $shopifyInventoryItem->external_id = null;
        $shopifyInventoryItem->save();
        if (isset($rexProduct)) {
            $previousSyncs = SyncJobsHistory::where('source', 'rex')
                ->where('queue', 'product')
                ->where('entity_id', $rexProduct->id)
                ->whereDate('created_at', Carbon::today())
                ->count();

            if ($previousSyncs >= self::MAX_DAILY_SYNCS) {
                Log::notice('Rex product ' . $rexProduct->id . ' cannot be resynced as it has already '
                    . 'been attempted too many times');
                return;
            }

            $variants = $shopifyProduct->shopifyProductVariants;
            $inventoryItemIds = [];
            foreach ($variants as $variant) {
                $inventoryItemIds[] = $variant->shopifyInventoryItem->id;
            }

            DB::table('sync_jobs')
                ->where('source', 'shopify')
                ->where('queue', 'product_inventory')
                ->whereIn('entity_id', $inventoryItemIds)
                ->where('direction', 'in')
                ->whereNull('reserved_at')
                ->delete();

            $rexProductSyncer = $this->syncerRepository->getSyncer($rexProduct);
            $rexProductSyncer->syncOut($rexProduct);
        }
    }
}
