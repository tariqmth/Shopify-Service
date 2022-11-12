<?php

namespace App\Models\Syncer;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ExternalEntityNotFoundException;
use App\Exceptions\ImpossibleTaskException;
use App\Exceptions\InvalidDataException;
use App\Models\Job\SyncJobsHistory;
use App\Models\Mapper\DisabledShopifyProductMapper;
use App\Models\Mapper\EnabledShopifyProductMapper;
use App\Models\Mapper\ShopifyProductMapper;
use App\Models\Mapper\ActiveShopifyProductMapper;
use App\Models\Mapper\InactiveShopifyProductMapper;
use App\Models\Mapper\ShopifyProductWithoutVariantsMapper;
use App\Models\Product\ShopifyProductRepository;
use App\Models\Product\ShopifyProductVariant;
use App\Models\Product\RexProductRepository;
use App\Models\Store\ShopifyStore;
use App\Packages\ShopifySdkFactory;
use App\Models\Product\ShopifyProduct;
use App\Queues\Jobs\SyncAllRexProductsOut;
use App\Queues\Jobs\SyncAllShopifyProductsOut;
use App\Queues\Jobs\SyncAllShopifyProductsOutCursively;
use App\Queues\Jobs\SyncShopifyProductIn;
use App\Queues\Jobs\SyncShopifyProductsPageOut;
use App\Queues\Jobs\SyncShopifyProductsPageOutCursively;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ShopifyProductSyncer extends ShopifySyncer
{
    const PRODUCTS_PER_PAGE = 250;
    const MAX_DAILY_SYNCS = 20;
    const UNIQUE_VARIANT_CONSTRAINT = 'shopify_product_variants_external_id_deleted_unique';

    protected $rexProductMapper;
    protected $shopifySdkFactory;
    protected $rexProductRepository;
    protected $rexProductSyncer;
    protected $shopifyProductRepository;
    protected $enabledShopifyProductMapper;
    protected $disabledShopifyProductMapper;
    protected $activeShopifyProductMapper;
    protected $inactiveShopifyProductMapper;
    protected $shopifyProductWithoutVariantsMapper;

    public function __construct(
        ShopifyProductMapper $rexProductMapper,
        ShopifySdkFactory $shopifySdkFactory,
        RexProductRepository $rexProductRepository,
        RexProductSyncer $rexProductSyncer,
        EnabledShopifyProductMapper $enabledShopifyProductMapper,
        DisabledShopifyProductMapper $disabledShopifyProductMapper,
        ActiveShopifyProductMapper $activeShopifyProductMapper,
        InactiveShopifyProductMapper $inactiveShopifyProductMapper,
        ShopifyProductRepository $shopifyProductRepository,
        ShopifyProductWithoutVariantsMapper $shopifyProductWithoutVariantsMapper
    ) {
        $this->rexProductMapper = $rexProductMapper;
        $this->shopifySdkFactory = $shopifySdkFactory;
        $this->rexProductRepository = $rexProductRepository;
        $this->rexProductSyncer = $rexProductSyncer;
        $this->enabledShopifyProductMapper = $enabledShopifyProductMapper;
        $this->disabledShopifyProductMapper = $disabledShopifyProductMapper;
        $this->activeShopifyProductMapper = $activeShopifyProductMapper;
        $this->inactiveShopifyProductMapper = $inactiveShopifyProductMapper;
        $this->shopifyProductRepository = $shopifyProductRepository;
        $this->shopifyProductWithoutVariantsMapper = $shopifyProductWithoutVariantsMapper;
    }

    public function syncOut(ShopifyProduct $shopifyProduct)
    {
        // todo
    }

    /*
     * Deprecated
     */
    public function syncAllOut(ShopifyStore $shopifyStore)
    {
        Log::warning('The syncAllOut method for Shopify products is deprecated. Cursor navigation should be used.');
        SyncAllShopifyProductsOut::dispatch($shopifyStore)
            ->onConnection('database_sync')
            ->onQueue('all_products');
    }

    public function syncAllOutCursively(ShopifyStore $shopifyStore, $sinceId)
    {
        SyncAllShopifyProductsOutCursively::dispatch($shopifyStore, $sinceId)
            ->onConnection('database_sync')
            ->onQueue('all_products');
    }

    /*
     * Deprecated
     */
    public function syncPageOut(ShopifyStore $shopifyStore, $pageNumber)
    {
        SyncShopifyProductsPageOut::dispatch($shopifyStore, $pageNumber)
            ->onConnection('database_sync')
            ->onQueue('all_products');
    }

    public function syncIn(ShopifyProduct $shopifyProduct, $productData)
    {
        $this->removeQueuedConflictsForSyncIn($shopifyProduct->id);
        if (!$this->conflictsRunningForSyncIn($shopifyProduct->id)) {
            SyncShopifyProductIn::dispatch($shopifyProduct, $productData)
                ->onConnection('database_sync')
                ->onQueue('product');
        }
    }

    public function syncInForOption(ShopifyProduct $shopifyProduct, $productData)
    {
        SyncShopifyProductIn::dispatch($shopifyProduct, $productData)
            ->onConnection('database_sync')
            ->onQueue('product_option');
    }

    public function enable(ShopifyProduct $shopifyProduct)
    {
        if (!$shopifyProduct->active) {
            throw new \Exception('Can not enable inactive product.');
        }
        if (!$shopifyProduct->hasBeenSynced()) {
            throw new \Exception('Can not enable product that has not been synced.');
        }
        $productData = $this->enabledShopifyProductMapper->getMappedData();
        SyncShopifyProductIn::dispatch($shopifyProduct, $productData)
            ->onConnection('database_sync')
            ->onQueue('product_enabler');
    }

    public function disable(ShopifyProduct $shopifyProduct)
    {
        if (!$shopifyProduct->hasBeenSynced()) {
            throw new \Exception('Can not disable product that has not been synced.');
        }
        $productData = $this->disabledShopifyProductMapper->getMappedData();
        SyncShopifyProductIn::dispatch($shopifyProduct, $productData)
            ->onConnection('database_sync')
            ->onQueue('product_enabler');
    }

    public function syncActiveStatus(ShopifyProduct $shopifyProduct)
    {
        if (!$shopifyProduct->hasBeenSynced()) {
            throw new \Exception('Can not reactivate product that has not been synced.');
        }
        if ($shopifyProduct->active) {
            $productData = $this->activeShopifyProductMapper->getMappedData($shopifyProduct);
        } else {
            $productData = $this->inactiveShopifyProductMapper->getMappedData($shopifyProduct);
        }
        SyncShopifyProductIn::dispatch($shopifyProduct, $productData)
            ->onConnection('database_sync')
            ->onQueue('product_enabler');
    }

    /*
     * Deprecated
     */
    public function performSyncAllOut($shopifyStoreId)
    {
        $shopifyStore = ShopifyStore::findOrFail($shopifyStoreId);
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);

        $response = $shopifySdk->products->readCount();

        $this->limitApiCalls($shopifyStore, $response->creditLeft());

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        }

        $count = $response->parsedResponse();
        $maxPage = (int) ceil($count/self::PRODUCTS_PER_PAGE);

        $shopifyStore->original_products = $count;
        $shopifyStore->save();

        for ($pageNumber = 1; $pageNumber <= $maxPage; $pageNumber++) {
            $this->syncPageOut($shopifyStore, $pageNumber);
        }

        if ((int) $count === 0 && $shopifyStore->setup_status === ShopifyStore::SETUP_STATUS_LOADING) {
            $shopifyStore->setup_status = ShopifyStore::SETUP_STATUS_CONFIRMATION;
            $shopifyStore->save();
        }
    }

    /*
     * Deprecated
     */
    public function performSyncPageOut($shopifyStoreId, $pageNumber)
    {
        $shopifyStore = ShopifyStore::findOrFail($shopifyStoreId);
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);

        $response = $shopifySdk->products->readList(['limit' => self::PRODUCTS_PER_PAGE, 'page' => $pageNumber]);

        $this->limitApiCalls($shopifyStore, $response->creditLeft());
        $body = $response->parsedResponse();

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        }

        foreach ($body as $productData) {
            $this->saveProductData($shopifyStore, $productData);
        }

        $maxPage = (int) ceil($shopifyStore->original_products/self::PRODUCTS_PER_PAGE);

        if ($pageNumber >= $maxPage && $shopifyStore->setup_status === ShopifyStore::SETUP_STATUS_LOADING) {
            $shopifyStore->setup_status = ShopifyStore::SETUP_STATUS_CONFIRMATION;
            $shopifyStore->save();
        }
    }

    /*
     * Cursor based navigation
     */
    public function performSyncAllOutCursively($shopifyStoreId, $sinceId)
    {
        $shopifyStore = ShopifyStore::findOrFail($shopifyStoreId);
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);

        $response = $shopifySdk->products->readList(['limit' => self::PRODUCTS_PER_PAGE, 'since_id' => $sinceId]);

        $this->limitApiCalls($shopifyStore, $response->creditLeft());
        $body = $response->parsedResponse();

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        }

        foreach ($body as $productData) {
            $this->saveProductData($shopifyStore, $productData);
        }

        if (null === $response->nextLink() || !count($body)) {
            if ($shopifyStore->setup_status === ShopifyStore::SETUP_STATUS_LOADING) {
                $shopifyStore->setup_status = ShopifyStore::SETUP_STATUS_CONFIRMATION;
                $shopifyStore->save();
            }
        } else {
            $this->syncAllOutCursively($shopifyStore, end($body)->id);
        }
    }

    public function saveProductData(ShopifyStore $shopifyStore, $productData)
    {
        $shopifyProduct = $this->shopifyProductRepository->getByExternalId($shopifyStore, $productData->id);
        if (isset($shopifyProduct)) {
            if ($shopifyProduct->title !== $productData->title) {
                $shopifyProduct->title = $productData->title;
                $shopifyProduct->save();
            }
        } else {
            $shopifyProduct = $this->shopifyProductRepository->createByExternalId(
                $shopifyStore,
                $productData->id,
                $productData->title
            );
        }
        foreach ($productData->variants as $variantData) {
            $shopifyProductVariant = $this->shopifyProductRepository->getVariantByExternalId(
                $shopifyProduct,
                $variantData->id
            );
            if (isset($shopifyProductVariant)) {
                if ($shopifyProductVariant->sku !== $variantData->sku) {
                    $shopifyProductVariant->sku = $variantData->sku;
                    $shopifyProductVariant->save();
                }
            } else {
                $this->shopifyProductRepository->createVariantByExternalId(
                    $shopifyProduct,
                    $variantData->id,
                    $variantData->sku
                );
            }
        }
        foreach ($shopifyProduct->shopifyProductVariants as $existingVariant) {
            $matchFound = false;
            foreach($productData->variants as $variantData) {
                if ($existingVariant->external_id === $variantData->id) {
                    $matchFound = true;
                }
            }
            if (!$matchFound) {
                $existingVariant->deleted = true;
                $existingVariant->save();
            }
        }
    }

    public function performSyncIn($shopifyProductId, $productData)
    {
        $shopifyProduct = ShopifyProduct::findOrFail($shopifyProductId);
        $shopifyStore = $shopifyProduct->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);

        if ($shopifyProduct->hasBeenSynced()) {
            $response = $shopifySdk->products->update($shopifyProduct->external_id, $productData);
        } else {
            $response = $shopifySdk->products->create($productData);
        }

        $this->limitApiCalls($shopifyStore, $response->creditLeft());
        $body = $response->parsedResponse();

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (ExternalEntityNotFoundException $e) {
            $this->handleEntityNotFound($shopifyProduct);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            $this->handleInvalidData($shopifyProduct, $body);
            throw new ImpossibleTaskException($e);
        }

        $shopifyProduct->external_id = $body->id;
        $shopifyProduct->synced_at = now();
        $shopifyProduct->sync_count++;
        if (isset($productData['version'])) {
            $shopifyProduct->latest_version = $productData['version'];
        }
        $shopifyProduct->save();

        $requiresResync = $this->newInventoryItemsExist($shopifyProduct);

        $variants = $shopifyProduct->shopifyProductVariants->where('deleted', '<>', '1')->all();
        if (count($variants)) {
            $this->updateVariantExternalIds($variants, $body->variants);
        }

        if ($requiresResync) {
            Log::debug('Rex product for Shopify product ' . $shopifyProduct->id . ' needs to be resynced ' .
                'for inventory items.');

            $rexProduct = $this->rexProductRepository->getFirstForShopifyProduct($shopifyProduct);

            $previousSyncs = SyncJobsHistory::where('source', 'rex')
                ->where('queue', 'product')
                ->where('entity_id', $rexProduct->id)
                ->whereDate('created_at', Carbon::today())
                ->count();

            if ($previousSyncs >= self::MAX_DAILY_SYNCS) {
                Log::notice('Rex product ' . $rexProduct->id . ' cannot be resynced for inventory as it has ' .
                    'already been attempted too many times');
            } else {
                $this->rexProductSyncer->syncOut($rexProduct);
            }
        }
    }

    private function performSyncEmptyVariants(ShopifyProduct $shopifyProduct)
    {
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyProduct->shopifyStore);

        $noVariantsData = $this->shopifyProductWithoutVariantsMapper->getMappedData();
        $response = $shopifySdk->products->update($shopifyProduct->external_id, $noVariantsData);

        $this->limitApiCalls($shopifyProduct->shopifyStore, $response->creditLeft());

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyProduct->shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (ExternalEntityNotFoundException $e) {
            $this->handleEntityNotFound($shopifyProduct);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            throw new ImpossibleTaskException($e);
        }

        foreach ($shopifyProduct->shopifyProductVariants as $variant) {
            $variant->deleted = true;
            $variant->save();
        }
    }

    private function updateVariantExternalIds($shopifyProductVariants, $returnedVariantsData)
    {
        foreach ($shopifyProductVariants as $shopifyProductVariant) {
            $variantData = $this->findMatchingVariantData($shopifyProductVariant, $returnedVariantsData);
            if ($variantData !== null) {
                if (!isset($shopifyProductVariant->external_id)) {
                    $shopifyProductVariant->external_id = $variantData->id;
                    $shopifyProductVariant->save();
                }
                if (isset($shopifyProductVariant->shopifyInventoryItem)
                    && !isset($shopifyProductVariant->shopifyInventoryItem->external_id)
                ) {
                    $shopifyInventoryItem = $shopifyProductVariant->shopifyInventoryItem;
                    $shopifyInventoryItem->external_id = $variantData->inventory_item_id;
                    $shopifyInventoryItem->save();
                }
            } else {
                try {
                    $shopifyProductVariant->deleted = true;
                    $shopifyProductVariant->save();
                } catch (\PDOException $e){
                    // Duplicate external ID and deleted flag on unique constraint
                    // Can happen if variants were erroneously set to deleted without removing from Shopify
                    if ($e->getCode() === "23000" && preg_match("/" . self::UNIQUE_VARIANT_CONSTRAINT . "/", $e)) {
                        $logMessage = 'Could not soft delete Shopify variant ' . $shopifyProductVariant->id .
                            ' with external ID ' . $shopifyProductVariant->external_id . ' due to a violation of the ' .
                            self::UNIQUE_VARIANT_CONSTRAINT . ' constraint. Hard deleting variant.';
                        Log::notice($logMessage);
                        $shopifyProductVariant->delete();
                    } else {
                        throw $e;
                    }
                }
            }
        }
    }

    private function findMatchingVariantData(ShopifyProductVariant $shopifyProductVariant, array $variantsData)
    {
        foreach ($variantsData as $variantData) {
            if ($shopifyProductVariant->external_id === $variantData->id
                || $shopifyProductVariant->sku === $variantData->sku
            ) {
                return $variantData;
            }
        }
    }

    private function newInventoryItemsExist(ShopifyProduct $shopifyProduct)
    {
        foreach ($shopifyProduct->shopifyProductVariants as $shopifyProductVariant) {
            if (!$shopifyProductVariant->deleted) {
                $shopifyInventoryItem = $shopifyProductVariant->shopifyInventoryItem;
                if (isset($shopifyInventoryItem) && !isset($shopifyInventoryItem->external_id)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function handleEntityNotfound(ShopifyProduct $shopifyProduct)
    {
        Log::error('Shopify product ' . $shopifyProduct->id . ' not found in Shopify.');
        $rexProduct = $this->rexProductRepository->getFirstForShopifyProduct($shopifyProduct);
        Log::notice('Deleting Shopify product ' . $shopifyProduct->id
            . ' from database and re-attempting sync of Rex product ' . $rexProduct->id);
        $shopifyProduct->delete();
        if (isset($rexProduct)) {
            $previousSyncs = SyncJobsHistory::where('source', 'rex')
                ->where('queue', 'product')
                ->where('entity_id', $rexProduct->id)
                ->whereDate('created_at', Carbon::today())
                ->count();

            if ($previousSyncs >= self::MAX_DAILY_SYNCS) {
                Log::notice('Rex product ' . $rexProduct->id . ' cannot be resynced as it has ' .
                    'already been attempted too many times');
                return;
            }

            $rexProduct->fresh(['shopifyProduct', 'rexProductGroup']);
            $this->rexProductSyncer->syncOut($rexProduct);
        }
    }

    private function handleInvalidData(ShopifyProduct $shopifyProduct, $responseBody)
    {
        $previousSyncs = SyncJobsHistory::where('source', 'shopify')
            ->where('queue', 'product')
            ->where('entity_id', $shopifyProduct->id)
            ->whereDate('created_at', Carbon::today())
            ->count();

        if ($previousSyncs >= self::MAX_DAILY_SYNCS) {
            Log::notice('Shopify product ' . $shopifyProduct->id . ' cannot be resynced as it has already '
                . 'been attempted too many times');
            return;
        }

        Log::notice('Deleting all variants and resyncing Shopify product ' . $shopifyProduct->id);
        $this->performSyncEmptyVariants($shopifyProduct);
        $rexProduct = $this->rexProductRepository->getFirstForShopifyProduct($shopifyProduct);
        if (isset($rexProduct)) {
            $this->rexProductSyncer->syncOut($rexProduct);
        }
    }

    private function removeQueuedConflictsForSyncIn($shopifyProductId)
    {
        DB::table('sync_jobs')
            ->where('source', 'shopify')
            ->where('queue', 'product')
            ->where('entity_id', $shopifyProductId)
            ->where('direction', 'in')
            ->whereNull('reserved_at')
            ->delete();
    }

    private function conflictsRunningForSyncIn($shopifyProductId)
    {
        $runningJobs = DB::table('sync_jobs')
            ->where('source', 'shopify')
            ->where('queue', 'product')
            ->where('entity_id', $shopifyProductId)
            ->where('direction', 'in')
            ->whereNotNull('reserved_at')
            ->get();
        return count($runningJobs) > 0;
    }
}
