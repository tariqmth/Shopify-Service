<?php

namespace App\Updaters;

use App\Models\Customer\ShopifyCustomer;
use App\Models\Store\RexSalesChannel;
use App\Models\Store\ShopifyStore;
use App\Models\Syncer\RexCustomerSyncer;
use Illuminate\Support\Facades\DB;

class UpdaterFor10505CustomerDuplicates implements Updater
{
    const NAME = '10505CustomerDuplicates';

    protected $rexCustomerSyncer;

    public function __construct(RexCustomerSyncer $rexCustomerSyncer)
    {
        $this->rexCustomerSyncer = $rexCustomerSyncer;
    }

    public function run()
    {
        $duplicateCustomers = ShopifyCustomer
            ::select(DB::raw('count(*) as count, shopify_customers.email as email, shopify_customers.shopify_store_id as shopify_store_id'))
            ->whereNotNull('email')
            ->groupBy('email')
            ->groupBy('shopify_store_id')
            ->having('count', '>', 1)
            ->get();

        $rexSalesChannelIds = [];
        $customersRemoved = 0;

        foreach ($duplicateCustomers as $duplicateCustomer) {
            $customerWithoutExternalId = ShopifyCustomer
                ::where('shopify_store_id', $duplicateCustomer->shopify_store_id)
                ->where('email', $duplicateCustomer->email)
                ->whereNull('external_id')
                ->first();
            if (isset($customerWithoutExternalId)) {
                $customerWithoutExternalId->delete();
                $customersRemoved++;
                $rexSalesChannelIds[] = $customerWithoutExternalId->shopifyStore->rex_sales_channel_id;
            }
        }

        echo 'Removed ' . $customersRemoved . ' customers. ';

        $rexSalesChannelIds = array_unique($rexSalesChannelIds);
        $salesChannelsSynced = 0;

        foreach ($rexSalesChannelIds as $rexSalesChannelId) {
            $rexSalesChannel = RexSalesChannel::find($rexSalesChannelId);
            $this->rexCustomerSyncer->syncAllOut($rexSalesChannel);
            $salesChannelsSynced++;
        }

        echo 'Bulk customers synced for ' . $salesChannelsSynced . ' sales channels. ';
    }

    public function getName()
    {
        return self::NAME;
    }
}