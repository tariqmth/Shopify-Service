<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer\ShopifyCustomerRepository;
use App\Models\Payment\ShopifyTransactionRepository;
use App\Models\Order\ShopifyOrderRepository;
use App\Models\Syncer\ShopifyCustomerSyncer;
use App\Models\Syncer\ShopifyOrderSyncer;
use App\Models\Syncer\ShopifyTransactionSyncer;
use App\Models\Store\ShopifyStore;
use Validator;

class ShopifyNotify extends Command
{
    protected $shopifyOrderRepository;
    protected $shopifyOrderSyncer;
    protected $shopifyCustomerRepository;
    protected $shopifyTransactionRepository;
    protected $shopifyCustomerSyncer;
    protected $shopifyTransactionSyncer;

    public function __construct(
        ShopifyOrderRepository $shopifyOrderRepository,
        ShopifyOrderSyncer $shopifyOrderSyncer,
        ShopifyCustomerRepository $shopifyCustomerRepository,
        ShopifyTransactionRepository $shopifyTransactionRepository,        
        ShopifyCustomerSyncer $shopifyCustomerSyncer,
        ShopifyTransactionSyncer $shopifyTransactionSyncer
    ) {
        parent::__construct();
        $this->shopifyOrderRepository = $shopifyOrderRepository;
        $this->shopifyOrderSyncer = $shopifyOrderSyncer;
        $this->shopifyCustomerRepository = $shopifyCustomerRepository;
        $this->shopifyTransactionRepository = $shopifyTransactionRepository;
        $this->shopifyCustomerSyncer = $shopifyCustomerSyncer;
        $this->shopifyTransactionSyncer = $shopifyTransactionSyncer;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify-connector:shopify-notify {subdomain} {entity} {externalIds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync a Shopify entity by external IDs';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $subdomain = $this->argument('subdomain');
        $entityType = $this->argument('entity');
        $externalIds = explode(',', $this->argument('externalIds'));

        $shopifyStore = ShopifyStore::where('subdomain', $subdomain)->firstOrFail();

        if ($entityType === 'order') {
            $this->syncOrder($shopifyStore, $externalIds);
        } elseif ($entityType === 'payment') {
            $this->syncTransaction($shopifyStore, $externalIds);
        } elseif ($entityType === 'customer') {
            $this->syncCustomer($shopifyStore, $externalIds);
        } else {
            throw new \Exception('Shopify entity ' . $entityType . ' is not supported.');
        }
    }

    protected function syncOrder(ShopifyStore $shopifyStore, $externalIds)
    {
        foreach ($externalIds as $externalId) {
            $shopifyOrder = $this->shopifyOrderRepository->getOrCreate($shopifyStore->id, $externalId);
            $this->shopifyOrderSyncer->syncOut($shopifyOrder);
        }
    }

    protected function syncTransaction(ShopifyStore $shopifyStore, $externalIds)
    {
        foreach ($externalIds as $externalId) {
            $shopifyTransaction = $this->shopifyTransactionRepository->get($externalId);
            $this->shopifyTransactionSyncer->syncOut($shopifyTransaction);
        }
    }
    protected function syncCustomer(ShopifyStore $shopifyStore, $externalIds)
    {
        foreach ($externalIds as $externalId) {
            $shopifyCustomer = $this->shopifyCustomerRepository->getOrCreate($shopifyStore->id, $externalId);
            if (!isset($shopifyCustomer->rexCustomer)) {
                $this->shopifyCustomerSyncer->syncOut($shopifyCustomer);
            }
        }
    }
}
