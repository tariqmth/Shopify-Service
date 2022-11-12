<?php

namespace App\Models\Syncer;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ExternalEntityNotFoundException;
use App\Exceptions\ImpossibleTaskException;
use App\Exceptions\InvalidDataException;
use App\Models\Location\ShopifyFulfillmentService;
use App\Models\Location\ShopifyFulfillmentServiceRepository;
use App\Models\Mapper\ShopifyFulfillmentServiceMapper;
use App\Models\Mapper\ShopifyWebhookMapper;
use App\Models\Notification\ShopifyWebhook;
use App\Models\Notification\ShopifyWebhookRepository;
use App\Models\Store\ShopifyStore;
use App\Packages\ShopifySdkFactory;
use App\Queues\Jobs\SyncShopifyFulfillmentServiceIn;
use App\Queues\Jobs\SyncShopifyWebhookIn;
use App\Queues\Jobs\SyncShopifyWebhooksOut;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookSyncer extends ShopifySyncer
{
    protected $shopifySdkFactory;
    protected $shopifyWebhookMapper;
    protected $shopifyWebhookRepository;

    public function __construct(
        ShopifySdkFactory $shopifySdkFactory,
        ShopifyWebhookMapper $shopifyWebhookMapper,
        ShopifyWebhookRepository $shopifyWebhookRepository
    ) {
        $this->shopifySdkFactory = $shopifySdkFactory;
        $this->shopifyWebhookMapper = $shopifyWebhookMapper;
        $this->shopifyWebhookRepository = $shopifyWebhookRepository;
    }

    public function syncOut(ShopifyStore $shopifyStore, bool $recurring = false)
    {
        SyncShopifyWebhooksOut::dispatch($shopifyStore, $recurring)
            ->onConnection('database_sync')
            ->onQueue('notification_service');
    }

    public function syncIn(ShopifyWebhook $shopifyWebhook)
    {
        SyncShopifyWebhookIn::dispatch($shopifyWebhook)
            ->onConnection('database_sync')
            ->onQueue('notification_service');
    }

    public function performSyncIn($shopifyWebhookId)
    {
        $shopifyWebhook = ShopifyWebhook::findOrFail($shopifyWebhookId);
        $shopifyStore = $shopifyWebhook->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);
        $mappedData = $this->shopifyWebhookMapper->getMappedData($shopifyWebhook);

        if ($shopifyWebhook->hasBeenSynced()) {
            $response = $shopifySdk->webhooks->update($shopifyWebhook->external_id, $mappedData);
        } else {
            $response = $shopifySdk->webhooks->create($mappedData);
        }

        $this->limitApiCalls($shopifyStore, $response->creditLeft());
        $body = $response->parsedResponse();

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (ExternalEntityNotFoundException $e) {
            $this->handleEntityNotfound($shopifyWebhook);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            throw new ImpossibleTaskException($e);
        }

        $shopifyWebhook->external_id = $body->id;
        $shopifyWebhook->save();
    }

    public function performSyncOut($shopifyStoreId)
    {
        $shopifyStore = ShopifyStore::findOrFail($shopifyStoreId);
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);
        $response = $shopifySdk->webhooks->readList();
        $returnedWebhooks = $response->parsedResponse();

        foreach ($this->shopifyWebhookRepository->all($shopifyStore->id) as $existingWebhook) {
            if ($existingWebhook->hasBeenSynced()
                && !$this->webhookExistsInShopify($existingWebhook, $returnedWebhooks)
            ) {
                $existingWebhook->delete();
            }
        }

        $newWebhooks = $this->shopifyWebhookRepository->updateAll($shopifyStore->id);
        foreach ($newWebhooks as $newWebhook) {
            $this->syncIn($newWebhook);
        }
    }

    public function performDelete($shopifyWebhookId)
    {
        $shopifyWebhook = ShopifyWebhook::findOrFail($shopifyWebhookId);
        $shopifyStore = $shopifyWebhook->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);

        if ($shopifyWebhook->hasBeenSynced()) {
            $response = $shopifySdk->webhooks->destroy($shopifyWebhook->external_id);
        } else {
            $shopifyWebhook->delete();
            return;
        }

        $this->limitApiCalls($shopifyStore, $response->creditLeft());

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (ExternalEntityNotFoundException $e) {
            $this->handleEntityNotfound($shopifyWebhook);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            throw new ImpossibleTaskException($e);
        }

        $shopifyWebhook->delete();
    }

    private function webhookExistsInShopify(ShopifyWebhook $shopifyWebhook, $returnedWebhooks)
    {
        foreach ($returnedWebhooks as $returnedWebhook) {
            if ($shopifyWebhook->external_id === $returnedWebhook->id) {
                return true;
            }
        }
        return false;
    }

    private function handleEntityNotfound(ShopifyWebhook $shopifyWebhook)
    {
        $shopifyStore = $shopifyWebhook->shopifyStore;
        Log::error('Shopify webhook ' . $shopifyWebhook->external_id
            . ' not found in Shopify store '
            . $shopifyStore->subdomain
            . '. Deleting external ID from webhook '
            . $shopifyWebhook->id
            . ' and resyncing.');
        $shopifyWebhook->external_id = null;
        $shopifyWebhook->save();
        $this->syncIn($shopifyWebhook);
    }
}
