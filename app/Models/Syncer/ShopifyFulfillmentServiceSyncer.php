<?php

namespace App\Models\Syncer;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ExternalEntityNotFoundException;
use App\Exceptions\ImpossibleTaskException;
use App\Exceptions\InvalidDataException;
use App\Models\Location\ShopifyFulfillmentService;
use App\Models\Location\ShopifyFulfillmentServiceRepository;
use App\Models\Mapper\ShopifyFulfillmentServiceMapper;
use App\Packages\ShopifySdkFactory;
use App\Queues\Jobs\SyncShopifyFulfillmentServiceIn;
use Illuminate\Support\Facades\Log;

class ShopifyFulfillmentServiceSyncer extends ShopifySyncer
{
    protected $shopifySdkFactory;
    protected $shopifyFulfillmentServiceMapper;
    protected $shopifyFulfillmentServiceRepository;

    public function __construct(
        ShopifySdkFactory $shopifySdkFactory,
        ShopifyFulfillmentServiceMapper $shopifyFulfillmentServiceMapper,
        ShopifyFulfillmentServiceRepository $shopifyFulfillmentServiceRepository
    ) {
        $this->shopifySdkFactory = $shopifySdkFactory;
        $this->shopifyFulfillmentServiceMapper = $shopifyFulfillmentServiceMapper;
        $this->shopifyFulfillmentServiceRepository = $shopifyFulfillmentServiceRepository;
    }

    public function syncOut(ShopifyFulfillmentService $shopifyFulfillmentService)
    {
        // todo
    }

    public function syncIn(ShopifyFulfillmentService $shopifyFulfillmentService)
    {
        SyncShopifyFulfillmentServiceIn::dispatch($shopifyFulfillmentService)
            ->onConnection('database_sync')
            ->onQueue('fulfillment_service');
    }

    public function performSyncIn($shopifyFulfillmentServiceId)
    {
        $shopifyFulfillmentService = ShopifyFulfillmentService::findOrFail($shopifyFulfillmentServiceId);
        $shopifyStore = $shopifyFulfillmentService->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);
        $mappedData = $this->shopifyFulfillmentServiceMapper->getMappedData();

        if ($shopifyFulfillmentService->hasBeenSynced()) {
            $response = $shopifySdk->fulfillment_services->update($shopifyFulfillmentService->external_id, $mappedData);
        } else {
            $response = $shopifySdk->fulfillment_services->create($mappedData);
        }

        $this->limitApiCalls($shopifyStore, $response->creditLeft());
        $body = $response->parsedResponse();

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (ExternalEntityNotFoundException $e) {
            $this->handleEntityNotfound($shopifyFulfillmentService);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            throw new ImpossibleTaskException($e);
        }

        $shopifyFulfillmentService->external_id = $body->id;
        $shopifyFulfillmentService->handle = $body->handle;
        $shopifyFulfillmentService->save();

        $location = $this->shopifyFulfillmentServiceRepository->createLocation($shopifyFulfillmentService);
        $location->external_id = $body->location_id;
        $location->save();
    }

    private function handleEntityNotfound(ShopifyFulfillmentService $shopifyFulfillmentService)
    {
        $shopifyStore = $shopifyFulfillmentService->shopifyStore;
        Log::error('Shopify fulfillment service ' . $shopifyFulfillmentService->external_id
            . ' not found in Shopify store '
            . $shopifyStore->subdomain
            . '. Deleting external ID from service '
            . $shopifyFulfillmentService->id
            . ' and resyncing.');
        $shopifyFulfillmentService->external_id = null;
        $shopifyFulfillmentService->save();
        $this->syncIn($shopifyFulfillmentService);
    }
}
