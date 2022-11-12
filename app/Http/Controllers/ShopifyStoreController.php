<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShopifyStoreCollection;
use App\Models\Location\ShopifyFulfillmentServiceRepository;
use App\Models\Notification\ShopifyWebhookRepository;
use App\Models\Product\ShopifyProduct;
use App\Models\Store\RexSalesChannel;
use App\Models\Store\ShopifyStoreAuth;
use App\Models\Syncer\ShopifyFulfillmentServiceSyncer;
use App\Models\Syncer\ShopifyProductSyncer;
use App\Models\Syncer\ShopifyStoreSyncer;
use App\Models\Syncer\ShopifyWebhookSyncer;
use Illuminate\Http\Request;
use App\Models\Store\ShopifyStore;
use App\Models\Client\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Validator;
use App\Http\Resources\ShopifyStore as ShopifyStoreResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Syncer\RexProductSyncer;

use App\Models\Mapper\ShopifyInventoryItemMapper;
use App\Models\Mapper\ShopifyInventoryLevelMapper;
use App\Models\Product\RexProduct;
use App\Models\Product\ShopifyProductRepository;
use App\Models\Product\RexProductRepository;
use App\Models\Mapper\ShopifyProductMapper;
use App\Models\Syncer\SyncerRepository;
use App\Models\Attribute\AttributeRepository;
use App\Models\Syncer\ShopifyInventoryItemSyncer;
use App\Packages\SkylinkSdkFactory;

class ShopifyStoreController extends Controller
{
    protected $shopifyStoreAuth;
    protected $shopifyFulfillmentServiceRepository;
    protected $shopifyFulfillmentServiceSyncer;
    protected $shopifyWebhookRepository;
    protected $shopifyWebhookSyncer;
    protected $shopifyProductSyncer;
    protected $shopifyStoreSyncer;

    protected $shopifyMapper;
    protected $syncerRepository;
    protected $productRepository;
    protected $shopifyProductRepository;
    protected $attributeRepository;
    protected $shopifyInventoryItemMapper;
    protected $shopifyInventoryLevelMapper;
    protected $shopifyInventoryItemSyncer;
    protected $skylinkSdkFactory;
    protected $rexProductSyncer;

    public function __construct(
        ShopifyStoreAuth $shopifyStoreAuth,
        ShopifyFulfillmentServiceRepository $shopifyFulfillmentServiceRepository,
        ShopifyFulfillmentServiceSyncer $shopifyFulfillmentServiceSyncer,
        ShopifyWebhookRepository $shopifyWebhookRepository,
        ShopifyWebhookSyncer $shopifyWebhookSyncer,
        ShopifyProductSyncer $shopifyProductSyncer,
        ShopifyStoreSyncer $shopifyStoreSyncer,

        ShopifyProductMapper $shopifyProductMapper,
        SyncerRepository $syncerRepository,
        RexProductRepository $productRepository,
        ShopifyProductRepository $shopifyProductRepository,
        AttributeRepository $attributeRepository,
        ShopifyInventoryItemMapper $shopifyInventoryItemMapper,
        ShopifyInventoryLevelMapper $shopifyInventoryLevelMapper,
        ShopifyInventoryItemSyncer $shopifyInventoryItemSyncer,
        SkylinkSdkFactory $skylinkSdkFactory,
        RexProductSyncer $rexProductSyncer

    ) {
        $this->shopifyStoreAuth = $shopifyStoreAuth;
        $this->shopifyFulfillmentServiceRepository = $shopifyFulfillmentServiceRepository;
        $this->shopifyFulfillmentServiceSyncer = $shopifyFulfillmentServiceSyncer;
        $this->shopifyWebhookRepository = $shopifyWebhookRepository;
        $this->shopifyWebhookSyncer = $shopifyWebhookSyncer;
        $this->shopifyProductSyncer = $shopifyProductSyncer;
        $this->shopifyStoreSyncer = $shopifyStoreSyncer;

        $this->shopifyMapper = $shopifyProductMapper;
        $this->syncerRepository = $syncerRepository;
        $this->productRepository = $productRepository;
        $this->shopifyProductRepository = $shopifyProductRepository;
        $this->attributeRepository = $attributeRepository;
        $this->shopifyInventoryItemMapper = $shopifyInventoryItemMapper;
        $this->shopifyInventoryLevelMapper = $shopifyInventoryLevelMapper;
        $this->shopifyInventoryItemSyncer = $shopifyInventoryItemSyncer;
        $this->skylinkSdkFactory = $skylinkSdkFactory;
        $this->rexProductSyncer = $rexProductSyncer;

    }

    public function post(Request $request, $clientId)
    {
        try {
            $client = Client::where('external_id', $clientId)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response('Client could not be found.', 404);
        }

        $data = json_decode($request->getContent(), true);

        Validator::make($data, [
            'shopify_store' => 'required'
        ])->validate();

        $shopifyStoreData = $data['shopify_store'];

        Validator::make($shopifyStoreData, [
            'subdomain' => 'required|unique:shopify_stores',
            'sales_channel_id' => 'required'
        ])->validate();

        $salesChannel = RexSalesChannel::firstOrCreate([
            'client_id' => $client->id,
            'external_id' => $shopifyStoreData['sales_channel_id']
        ]);

        $existingStoreForSalesChannel = ShopifyStore
            ::where('client_id', $client->id)
            ->where('rex_sales_channel_id', $salesChannel->id)
            ->first();

        if (isset($existingStoreForSalesChannel)) {
            return response('Only one Shopify store can be created for client ' . $client->external_id
                . ' on sales channel ' . $salesChannel->external_id,
                422
            );
        }

        $shopifyStore = new ShopifyStore;
        $shopifyStore->client_id = $client->id;
        $shopifyStore->rex_sales_channel_id = $salesChannel->id;
        $shopifyStore->subdomain = array_get($shopifyStoreData, 'subdomain');
        $shopifyStore->setup_status = ShopifyStore::SETUP_STATUS_NEW;
        $shopifyStore->enabled = false;
        $shopifyStore->inventory_buffer = 0;
        $shopifyStore->preorders = 1;

        try {
            $this->updateOptionalFields($shopifyStore, $shopifyStoreData);
        } catch (\Exception $e) {
            return response($e->getMessage(), 422);
        }

        $shopifyStore->save();

        $this->performNewStoreTasks($shopifyStore);

        if ($shopifyStore->setup_status !== ShopifyStore::SETUP_STATUS_NEW) {
            $this->performSetupTasks($shopifyStore);
        }

        return new ShopifyStoreResource($shopifyStore);
    }

    public function get(Request $request, $clientId, $subdomain)
    {
        try {
            $shopifyStore = $this->findShopifyStore($clientId, $subdomain);
        } catch (ModelNotFoundException $e) {
             return response('Shopify store could not be found.', 404);
        }

        return new ShopifyStoreResource($shopifyStore);
    }

    public function getDirectly(Request $request, $subdomain)
    {
        try {
            $shopifyStore = $this->findShopifyStoreDirectly($subdomain);
        } catch (ModelNotFoundException $e) {
             return response('Shopify store could not be found.', 404);
        }

        return new ShopifyStoreResource($shopifyStore);
    }

    public function put(Request $request, $clientId, $subdomain)
    {
        try {
            $shopifyStore = $this->findShopifyStore($clientId, $subdomain);
        } catch (ModelNotFoundException $e) {
            return response('Shopify store could not be found.', 404);
        }

        $data = json_decode($request->getContent(), true);
        Validator::make($data, [
            'shopify_store' => 'required'
        ])->validate();

        $shopifyStoreData = $data['shopify_store'];
        if (array_key_exists('inventory_buffer', $shopifyStoreData))
        {
            // Vaidate inventory buffer to be positive integer
            Validator::make($shopifyStoreData, [
                'inventory_buffer' => 'required|integer|min:0',
            ])->validate();
        }

        if (array_key_exists('preorders', $shopifyStoreData))
        {
            // Vaidate preorders 
            // accepts an id indicating current setting:
            // 1: Disabled
            // 2: Sell On Order stock for Pre-Order Products
            // 3: Accept Pre-Orders for Pre-Order Products
            $valid_preorder_values = [1,2,3];
            Validator::make($shopifyStoreData, [
                'preorders' => 'required|integer|in:'. implode(',',$valid_preorder_values)
            ])->validate();
        }

        $setupStatusChanged = array_key_exists('setup_status', $shopifyStoreData)
            && $shopifyStore->setup_status !== $shopifyStoreData['setup_status'];

        $newCredentials = $this->accessTokenChanged($shopifyStore, $shopifyStoreData);
    
        // Resync Products when the Default Inventory Buffer is updated
        $defaultInventoryBufferChanged = array_key_exists('inventory_buffer', $shopifyStoreData)
            && $shopifyStore->inventory_buffer !== $shopifyStoreData['inventory_buffer'];


        try {
            $this->updateOptionalFields($shopifyStore, $shopifyStoreData);
        } catch (\Exception $e) {
            return response($e->getMessage(), 422);
        }
        $shopifyStore->save();

        if ($newCredentials) {
            $this->performNewStoreTasks($shopifyStore);
        }

        if ($setupStatusChanged) {
            $this->performSetupTasks($shopifyStore);
        }

        // Resync ALL Products when the Default Inventory Buffer is updated
        if ($defaultInventoryBufferChanged){
            // Sync all product matched with product type id assign to buffer group
            $rexSalesChannel = RexSalesChannel::findOrFail($shopifyStore->rex_sales_channel_id);
            $this->rexProductSyncer->syncAllOut($rexSalesChannel);
        }

        return new ShopifyStoreResource($shopifyStore);
    }

    public function delete(Request $request, $clientId, $subdomain)
    {
        try {
            $shopifyStore = $this->findShopifyStore($clientId, $subdomain);
        } catch (ModelNotFoundException $e) {
             return response('Shopify store could not be found.', 404);
        }

        $response = 'Deleted Shopify store from the integration.';

        $uninstallationWebhook = $this->shopifyWebhookRepository->get($shopifyStore->id, 'app/uninstalled');

        if ($uninstallationWebhook !== null) {
            try {
                $this->shopifyWebhookSyncer->performDelete($uninstallationWebhook->id);
            } catch (\Exception $e) {
                Log::error($e);
            }
        }

        try {
            $this->shopifyStoreAuth->disconnect($shopifyStore);
        } catch (\Exception $e) {
            Log::error($e);
            $response .= ' We were unable to uninstall the Retail Express app from your Shopify account. '
                . 'Please ensure that it is disconnected in Shopify.';
        }

        $shopifyStore->enabled = false;
        $shopifyStore->clearCredentials();
        $shopifyStore->save();
        $rexSalesChannel = $shopifyStore->rexSalesChannel;
        $shopifyStore->deleteAllChildren();
        $shopifyStore->delete();
        $rexSalesChannel->deleteAllChildren();
        $rexSalesChannel->delete();

        return response($response, 200);
    }

    public function all(Request $request, $clientId)
    {
        try {
            $shopifyStores = $this->findShopifyStores($clientId);
        } catch (ModelNotFoundException $e) {
             return response('Client could not be found.', 404);
        }

        return new ShopifyStoreCollection($shopifyStores);
    }

    private function updateOptionalFields($shopifyStore, $shopifyData)
    {
        $shopifyStore->access_token = array_get($shopifyData, 'access_token', $shopifyStore->access_token);
        $shopifyStore->api_key      = array_get($shopifyData, 'api_key', $shopifyStore->api_key);
        $shopifyStore->password     = array_get($shopifyData, 'password', $shopifyStore->password);
        $shopifyStore->enabled      = array_get($shopifyData, 'enabled', $shopifyStore->enabled);
        $shopifyStore->setup_status = array_get($shopifyData, 'setup_status', $shopifyStore->setup_status);
        $shopifyStore->full_domain  = array_get($shopifyData, 'full_domain', $shopifyStore->full_domain);
        $shopifyStore->inventory_buffer  = array_get($shopifyData, 'inventory_buffer', $shopifyStore->inventory_buffer);
        $shopifyStore->preorders  = array_get($shopifyData, 'preorders', $shopifyStore->preorders);


        if ($shopifyStore->enabled
            && empty($shopifyStore->access_token)
            && (empty($shopifyStore->api_key) || empty($shopifyStore->password))
        ) {
            throw new \Exception('Cannot allow enabled Shopify store with no access token.');
        }

        if ($shopifyStore->enabled && $shopifyStore->client->license !== 'basic') {
            throw new \Exception('Cannot allow enabled Shopify store with unlicensed client.');
        }

        return $shopifyStore;
    }

    private function findShopifyStoreDirectly($subdomain)
    {
        $shopifyStore = ShopifyStore
            ::where('subdomain', $subdomain)
            ->firstOrFail();
        return $shopifyStore;
    }

    private function findShopifyStore($clientId, $subdomain)
    {
        $client = Client::where('external_id', $clientId)->firstOrFail();
        $shopifyStore = ShopifyStore
            ::where('shopify_stores.client_id', $client->id)
            ->where('subdomain', $subdomain)
            ->firstOrFail();
        return $shopifyStore;
    }

    private function findShopifyStores($clientId)
    {
        $client = Client::where('external_id', $clientId)->firstOrFail();
        return $client->shopifyStores;
    }

    private function performSetupTasks(ShopifyStore $shopifyStore)
    {
        switch ($shopifyStore->setup_status) {
            case ShopifyStore::SETUP_STATUS_LOADING: $this->performLoadingTasks($shopifyStore); break;
            case ShopifyStore::SETUP_STATUS_COMPLETE: $this->performCompletionTasks($shopifyStore); break;
        }
    }

    private function performNewStoreTasks(ShopifyStore $shopifyStore)
    {
        $updatedWebhook = $this->shopifyWebhookRepository->updateUninstallation($shopifyStore->id);
        if (isset($updatedWebhook)) {
            $this->shopifyWebhookSyncer->performSyncIn($updatedWebhook->id);
        }

        if (!isset($shopifyStore->shopifyFulfillmentService)
            || !$shopifyStore->shopifyFulfillmentService->hasBeenSynced()
        ) {
            $fulfillmentService = $this->shopifyFulfillmentServiceRepository->createFulfillmentService($shopifyStore);
            $this->shopifyFulfillmentServiceSyncer->syncIn($fulfillmentService);
        }
    }

    private function performLoadingTasks(ShopifyStore $shopifyStore)
    {
        ShopifyProduct::where('shopify_store_id', $shopifyStore->id)->delete();
        $this->shopifyProductSyncer->syncAllOutCursively($shopifyStore, 0);
    }

    private function performCompletionTasks(ShopifyStore $shopifyStore)
    {
        $this->shopifyStoreSyncer->syncOut($shopifyStore);

        $updatedWebhooks = $this->shopifyWebhookRepository->updateAll($shopifyStore->id);
        foreach ($updatedWebhooks as $updatedWebhook) {
            $this->shopifyWebhookSyncer->syncIn($updatedWebhook);
        }
    }

    private function accessTokenChanged(ShopifyStore $shopifyStore, $shopifyStoreData)
    {
        $newValue = array_get($shopifyStoreData, 'access_token', $shopifyStore->access_token);
        return isset($newValue) && $newValue !== $shopifyStore->access_token;
    }
}
