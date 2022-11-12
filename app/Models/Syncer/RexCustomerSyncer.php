<?php

namespace App\Models\Syncer;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Client\Client;
use App\Models\Customer\RexCustomer;
use App\Models\Customer\RexCustomerRepository;
use App\Models\Customer\ShopifyCustomerRepository;
use App\Models\Mapper\RexCustomerMapperFromShopify;
use App\Models\Mapper\RexOrderMapperFromShopify;
use App\Models\Mapper\ShopifyCustomerMapper;
use App\Models\Order\RexOrder;
use App\Models\Store\RexSalesChannel;
use App\Packages\SkylinkSdkFactory;
use App\Queues\Jobs\ProcessRexEDSNotification;
use App\Queues\Jobs\SyncAllRexCustomersOut;
use App\Queues\Jobs\SyncRexCustomerInFromShopify;
use App\Queues\Jobs\SyncRexCustomerOut;
use App\Queues\Jobs\SyncRexOrderIn;
use RetailExpress\SkyLink\Sdk\Apis\V2\Api as RexClient;
use RetailExpress\SkyLink\Sdk\Customers\CustomerId as RexCustomerIdData;
use RetailExpress\SkyLink\Sdk\Customers\V2CustomerRepository as RexCustomerClient;
use RetailExpress\SkyLink\Sdk\ValueObjects\SalesChannelId as RexSalesChannelIdData;
use ValueObjects\Web\Url;
use ValueObjects\Identity\UUID as Uuid;
use ValueObjects\StringLiteral\StringLiteral;

class RexCustomerSyncer extends RexSyncer
{
    const MAX_JOBS = 1000;

    protected $rexCustomerMapperFromShopify;
    protected $shopifyCustomerRepository;
    protected $syncerRepository;
    protected $rexCustomerRepository;
    protected $shopifyCustomerMapper;
    protected $skylinkSdkFactory;

    public function __construct(
        ShopifyCustomerMapper $shopifyCustomerMapper,
        ShopifyCustomerRepository $shopifyCustomerRepository,
        SyncerRepository $syncerRepository,
        RexCustomerMapperFromShopify $rexCustomerMapperFromShopify,
        RexCustomerRepository $rexCustomerRepository,
        SkylinkSdkFactory $skylinkSdkFactory
    ) {
        $this->shopifyCustomerMapper = $shopifyCustomerMapper;
        $this->shopifyCustomerRepository = $shopifyCustomerRepository;
        $this->syncerRepository = $syncerRepository;
        $this->rexCustomerMapperFromShopify = $rexCustomerMapperFromShopify;
        $this->rexCustomerRepository = $rexCustomerRepository;
        $this->skylinkSdkFactory = $skylinkSdkFactory;
    }

    public function syncOut(RexCustomer $rexCustomer)
    {
        SyncRexCustomerOut::dispatch($rexCustomer)
            ->onConnection('database_sync')
            ->onQueue('customer');
    }

    public function performSyncOut($rexCustomerId)
    {
        $rexCustomer = RexCustomer::findOrFail($rexCustomerId);
        $client = $rexCustomer->rexSalesChannel->client;
        $this->limitApiCalls($client);
        $rexCustomerData = $this->fetchRexCustomerData($rexCustomer);

        $emailAddress = $rexCustomerData->getBillingContact()->getEmailAddress()->toNative();
        if (isset($emailAddress) && $rexCustomer->email !== $emailAddress) {
            $rexCustomer->email = $emailAddress;
            $rexCustomer->save();
        }

        $shopifyCustomer = $this->shopifyCustomerRepository->getOrCreateForRexCustomer($rexCustomer);

        if ($shopifyCustomer->hasBeenSynced()) {
            $mappedData = $this->shopifyCustomerMapper->getMappedData($rexCustomerData);
        } else {
            $mappedData = $this->shopifyCustomerMapper->getInitialMappedData($rexCustomerData);
        }

        $shopifyCustomerSyncer = $this->syncerRepository->getSyncer($shopifyCustomer);
        $shopifyCustomerSyncer->syncIn($shopifyCustomer, $mappedData);
    }

    public function syncAllOut(RexSalesChannel $rexSalesChannel)
    {
        SyncAllRexCustomersOut::dispatch($rexSalesChannel)
            ->onConnection('database_sync')
            ->onQueue('all_customers');
    }

    public function performSyncAllOut($rexSalesChannelId)
    {
        $rexSalesChannel = RexSalesChannel::findOrFail($rexSalesChannelId);
        $customerIds = $this->fetchRexCustomerIds($rexSalesChannel);

        $count = count($customerIds);
        if ($count > self::MAX_JOBS) {
            for ($i = 0; $i < $count; $i += self::MAX_JOBS) {
                $notification = new \stdClass();
                $notification->Type = 'Customer';
                $notification->List = [];
                for ($id = $i; $id < $count && $id < $i + self::MAX_JOBS; $id++) {
                    $notification->List[] = $customerIds[$id]->toNative();
                }
                ProcessRexEDSNotification::dispatch(
                    $rexSalesChannel->client->external_id,
                    $notification,
                    $rexSalesChannel->external_id)
                    ->onConnection('database_sync')
                    ->onQueue('notification');
            }
        } else {
            foreach ($customerIds as $customerId) {
                $customer = $this->rexCustomerRepository->getOrCreate($rexSalesChannelId, $customerId->toNative());
                $this->syncOut($customer);
            }
        }
    }

    public function syncInFromShopify(RexCustomer $rexCustomer, $shopifyCustomerData)
    {
        SyncRexCustomerInFromShopify::dispatch($rexCustomer, $shopifyCustomerData)
            ->onConnection('database_sync')
            ->onQueue('customer');
    }

    public function performSyncInFromShopify($rexCustomerId, $shopifyCustomerData)
    {
        $rexCustomer = RexCustomer::findOrFail($rexCustomerId);

        $client = $rexCustomer->rexSalesChannel->client;
        $this->limitApiCalls($client);
        $mappedData = $this->rexCustomerMapperFromShopify->getMappedData($rexCustomer, $shopifyCustomerData);
        $rexSalesChannelIdData = RexSalesChannelIdData::fromNative($rexCustomer->rexSalesChannel->external_id);
        $rexCustomerClient = $this->getRexCustomerClient($client);
        $rexCustomerClient->add($mappedData, $rexSalesChannelIdData);
        $rexCustomer->fresh();

        if ($rexCustomer->hasBeenSynced()) {
            throw new ImpossibleTaskException('Race condition! Customer has been duplicated in Rex.');
        } elseif ($mappedData->getId() !== null) {
            $rexCustomer->external_id = $mappedData->getId()->toNative();
            $rexCustomer->save();
        } else {
            throw new \Exception('Customer not synced to Rex correctly.');
        }
    }

    private function getRexCustomerClient(Client $client)
    {
        $api = $this->skylinkSdkFactory->getApi($client);
        return new RexCustomerClient($api);
    }

    private function fetchRexCustomerData(RexCustomer $rexCustomer)
    {
        $rexSalesChannel = $rexCustomer->rexSalesChannel;
        $rexSalesChannelIdData = RexSalesChannelIdData::fromNative($rexSalesChannel->external_id);
        $rexCustomerIdData = RexCustomerIdData::fromNative($rexCustomer->external_id);
        $customerClient = $this->getRexCustomerClient($rexSalesChannel->client);
        return $customerClient->find($rexCustomerIdData, $rexSalesChannelIdData);
    }

    private function fetchRexCustomerIds(RexSalesChannel $rexSalesChannel)
    {
        $rexSalesChannelIdData = RexSalesChannelIdData::fromNative($rexSalesChannel->external_id);
        $customerClient = $this->getRexCustomerClient($rexSalesChannel->client);
        return $customerClient->allIds($rexSalesChannelIdData);
    }
}
