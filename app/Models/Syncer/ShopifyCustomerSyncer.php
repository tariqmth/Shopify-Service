<?php

namespace App\Models\Syncer;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ExternalEntityNotFoundException;
use App\Exceptions\ImpossibleTaskException;
use App\Exceptions\InvalidDataException;
use App\Models\Customer\RexCustomerRepository;
use App\Models\Customer\ShopifyCustomer;
use App\Packages\ShopifySdkFactory;
use App\Queues\Jobs\SyncShopifyCustomerExternalIdOut;
use App\Queues\Jobs\SyncShopifyCustomerIn;
use Illuminate\Support\Facades\Log;

class ShopifyCustomerSyncer extends ShopifySyncer
{
    protected $shopifySdkFactory;
    protected $rexCustomerRepository;
    protected $syncerRepository;

    public function __construct(
        ShopifySdkFactory $shopifySdkFactory,
        RexCustomerRepository $rexCustomerRepository,
        SyncerRepository $syncerRepository
    ) {
        $this->shopifySdkFactory = $shopifySdkFactory;
        $this->rexCustomerRepository = $rexCustomerRepository;
        $this->syncerRepository = $syncerRepository;
    }

    public function syncOut(ShopifyCustomer $shopifyCustomer, $shopifyCustomerData = null)
    {
        if (isset($shopifyCustomerData)) {
            $this->performSyncOut($shopifyCustomer->id, $shopifyCustomerData);
        }
    }

    public function performSyncOut($shopifyCustomerId, $shopifyCustomerData = null)
    {
        $shopifyCustomer = ShopifyCustomer::findOrFail($shopifyCustomerId);

        if (!isset($shopifyCustomerData)) {
            $shopifyCustomerData = $this->fetchShopifyCustomerData($shopifyCustomer);
        }

        if ($shopifyCustomer->email !== $shopifyCustomerData->email) {
            $shopifyCustomer = $shopifyCustomerData->email;
            $shopifyCustomer->save();
        }

        $rexCustomer = $this->rexCustomerRepository->getOrCreateForShopifyCustomer($shopifyCustomer);
        $rexCustomerSyncer = $this->syncerRepository->getSyncer($rexCustomer);
        $rexCustomerSyncer->syncInFromShopify($rexCustomer, $shopifyCustomerData);
    }

    public function syncOutExternalId(ShopifyCustomer $shopifyCustomer)
    {
        SyncShopifyCustomerExternalIdOut::dispatch($shopifyCustomer)
            ->onConnection('database_sync')
            ->onQueue('customer');
    }

    public function performSyncOutExternalId($shopifyCustomerId)
    {
        $shopifyCustomer = ShopifyCustomer::findOrFail($shopifyCustomerId);
        $numCustomerEmails = ShopifyCustomer
            ::where('email', $shopifyCustomer->email)
            ->where('shopify_store_id', $shopifyCustomer->shopify_store_id)
            ->count();

        if ($numCustomerEmails > 1) {
            throw new ImpossibleTaskException('Cannot proceed to match Shopify customer to Rex customer '
                . 'by email address because there are multiple customers in DB with the same email address.');
        }

        $shopifyCustomersData = $this->fetchShopifyCustomersDataByEmail($shopifyCustomer);

        if (!$shopifyCustomer->hasBeenSynced() && count($shopifyCustomersData)) {
            $shopifyCustomerData = reset($shopifyCustomersData);
            $shopifyCustomer->external_id = $shopifyCustomerData->id;
            $shopifyCustomer->save();
        }
    }

    public function syncIn(ShopifyCustomer $shopifyCustomer, $shopifyCustomerData)
    {
        SyncShopifyCustomerIn::dispatch($shopifyCustomer, $shopifyCustomerData)
            ->onConnection('database_sync')
            ->onQueue('customer');
    }

    public function performSyncIn($shopifyCustomerId, $shopifyCustomerData)
    {
        $shopifyCustomer = ShopifyCustomer::findOrFail($shopifyCustomerId);
        $returnedCustomerData = $this->pushShopifyCustomerData($shopifyCustomer, $shopifyCustomerData);
        if (!$shopifyCustomer->hasBeenSynced()) {
            $shopifyCustomer->external_id = $returnedCustomerData->id;
            $shopifyCustomer->save();
        }
        if ($shopifyCustomer->email !== $returnedCustomerData->email) {
            $shopifyCustomer->email = $returnedCustomerData->email;
            $shopifyCustomer->save();
        }
    }

    private function fetchShopifyCustomerData(ShopifyCustomer $shopifyCustomer)
    {
        $shopifyStore = $shopifyCustomer->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);
        $response = $shopifySdk->customers->get($shopifyCustomer->external_id);
        $this->limitApiCalls($shopifyStore, $response->creditLeft());

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (ExternalEntityNotFoundException $e) {
            $this->handleEntityNotfound($shopifyCustomer);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            throw new ImpossibleTaskException($e);
        }

        return $response->parsedResponse();
    }

    private function fetchShopifyCustomersDataByEmail(ShopifyCustomer $shopifyCustomer)
    {
        if (!isset($shopifyCustomer->email)) {
            throw new \Exception('Cannot fetch Shopify customer ' . $shopifyCustomer->id
                . ' by email as no email address could be found.');
        }

        $shopifyStore = $shopifyCustomer->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);
        $response = $shopifySdk->customers->search(['query' => 'email:' . $shopifyCustomer->email]);
        $this->limitApiCalls($shopifyStore, $response->creditLeft());

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            throw new ImpossibleTaskException($e);
        }

        return $response->parsedResponse();
    }

    private function pushShopifyCustomerData(ShopifyCustomer $shopifyCustomer, $shopifyCustomerData)
    {
        $shopifyStore = $shopifyCustomer->shopifyStore;
        $shopifySdk = $this->shopifySdkFactory->getSdk($shopifyStore);

        if ($shopifyCustomer->hasBeenSynced()) {
            $response = $shopifySdk->customers->update($shopifyCustomer->external_id, $shopifyCustomerData);
        } else {
            $response = $shopifySdk->customers->create($shopifyCustomerData);
        }

        $this->limitApiCalls($shopifyStore, $response->creditLeft());

        try {
            $this->verifyReponse($response);
        } catch (AuthenticationException $e) {
            $this->handleAuthFailure($shopifyStore);
            throw new ImpossibleTaskException($e);
        } catch (ExternalEntityNotFoundException $e) {
            $this->handleEntityNotfound($shopifyCustomer);
            throw new ImpossibleTaskException($e);
        } catch (InvalidDataException $e) {
            $this->syncOutExternalId($shopifyCustomer);
            throw new ImpossibleTaskException($e);
        }

        return $response->parsedResponse();
    }

    private function handleEntityNotfound(ShopifyCustomer $shopifyCustomer)
    {
        $shopifyStore = $shopifyCustomer->shopifyStore;
        Log::error('Shopify customer ' . $shopifyCustomer->external_id
            . ' not found in Shopify store '
            . $shopifyStore->subdomain
            . '. Deleting and resyncing rex product.');
        $rexCustomer = $shopifyCustomer->rexCustomer;
        $rexCustomerSyncer = $this->syncerRepository->getSyncer($rexCustomer);
        $rexCustomerSyncer->syncOut($rexCustomer);
        $shopifyCustomer->delete();
    }
}
