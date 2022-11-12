<?php

namespace App\Console\Commands;

use App\Models\Syncer\RexAttributeOptionSyncer;
use App\Models\Syncer\RexCustomerSyncer;
use App\Models\Syncer\RexOrderSyncer;
use App\Models\Syncer\RexOutletSyncer;
use App\Models\Syncer\RexPaymentSyncer;
use App\Models\Syncer\RexProductSyncer;
use App\Models\Syncer\RexVoucherSyncer;
use App\Models\Syncer\ShopifyCustomerSyncer;
use App\Models\Syncer\ShopifyFulfillmentServiceSyncer;
use App\Models\Syncer\ShopifyFulfillmentSyncer;
use App\Models\Syncer\ShopifyInventoryItemSyncer;
use App\Models\Syncer\ShopifyOrderSyncer;
use App\Models\Syncer\ShopifyProductSyncer;
use App\Models\Syncer\ShopifyStoreSyncer;
use App\Models\Syncer\ShopifyTransactionSyncer;
use App\Models\Syncer\ShopifyVoucherSyncer;
use App\Models\Syncer\ShopifyWebhookSyncer;
use Illuminate\Console\Command;

class Sync extends Command
{
    const QUEUED_MESSAGE = '%s %s %d has been queued to sync %s from %s.';
    const SYNCED_MESSAGE = '%s %s %d has been synced %s from %s.';

    protected $shopifyOrderSyncer;
    protected $shopifyTransactionSyncer;

    public function __construct(
        RexAttributeOptionSyncer $rexAttributeOptionSyncer,
        RexCustomerSyncer $rexCustomerSyncer,
        RexOrderSyncer $rexOrderSyncer,
        RexOutletSyncer $rexOutletSyncer,
        RexPaymentSyncer $rexPaymentSyncer,
        RexProductSyncer $rexProductSyncer,
        RexVoucherSyncer $rexVoucherSyncer,
        ShopifyCustomerSyncer $shopifyCustomerSyncer,
        ShopifyFulfillmentServiceSyncer $shopifyFulfillmentServiceSyncer,
        ShopifyFulfillmentSyncer $shopifyFulfillmentSyncer,
        ShopifyInventoryItemSyncer $shopifyInventoryItemSyncer,
        ShopifyOrderSyncer $shopifyOrderSyncer,
        ShopifyProductSyncer $shopifyProductSyncer,
        ShopifyStoreSyncer $shopifyStoreSyncer,
        ShopifyTransactionSyncer $shopifyTransactionSyncer,
        ShopifyVoucherSyncer $shopifyVoucherSyncer,
        ShopifyWebhookSyncer $shopifyWebhookSyncer
    ) {
        parent::__construct();
        $this->rexAttributeOptionSyncer = $rexAttributeOptionSyncer;
        $this->rexCustomerSyncer = $rexCustomerSyncer;
        $this->rexOrderSyncer = $rexOrderSyncer;
        $this->rexOutletSyncer = $rexOutletSyncer;
        $this->rexPaymentSyncer = $rexPaymentSyncer;
        $this->rexProductSyncer = $rexProductSyncer;
        $this->rexVoucherSyncer = $rexVoucherSyncer;
        $this->shopifyCustomerSyncer = $shopifyCustomerSyncer;
        $this->shopifyFulfillmentServiceSyncer = $shopifyFulfillmentServiceSyncer;
        $this->shopifyFulfillmentSyncer = $shopifyFulfillmentSyncer;
        $this->shopifyInventoryItemSyncer = $shopifyInventoryItemSyncer;
        $this->shopifyOrderSyncer = $shopifyOrderSyncer;
        $this->shopifyProductSyncer = $shopifyProductSyncer;
        $this->shopifyStoreSyncer = $shopifyStoreSyncer;
        $this->shopifyTransactionSyncer = $shopifyTransactionSyncer;
        $this->shopifyVoucherSyncer = $shopifyVoucherSyncer;
        $this->shopifyWebhookSyncer = $shopifyWebhookSyncer;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify-connector:sync {direction} {source} {entity} {id} {--last_id=} {--queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync a Rex or Shopify entity';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $firstId = (int) $this->argument('id');
        $lastId = $this->option('last_id') ? (int) $this->option('last_id') : $firstId;

        if (!in_array($this->argument('source'), ['rex', 'shopify'])) {
            throw new \Exception('Source must be rex or shopify');
        }

        $syncerName = $this->argument('source') . ucfirst($this->argument('entity')) . 'Syncer';
        if (isset($this->$syncerName)) {
            $syncer = $this->$syncerName;
        } else {
            throw new \Exception('Cannot sync entity type ' . $this->argument('entity'));
        }

        if (!in_array($this->argument('direction'), ['in', 'out'])) {
            throw new \Exception('Direction must be in or out');
        }

        for ($id = $firstId; $id <= $lastId; $id++) {
            if ($this->option('queue')) {
                $function = 'sync' . ucfirst($this->argument('direction'));
                $syncer->$function($id);
                $this->info(sprintf(
                    self::QUEUED_MESSAGE,
                    $this->argument('source'),
                    $this->argument('entity'),
                    $id,
                    $this->argument('direction'),
                    $this->argument('source')
                ));
            } else {
                $function = 'performSync' . ucfirst($this->argument('direction'));
                $syncer->$function($id);
                $this->info(sprintf(
                    self::SYNCED_MESSAGE,
                    $this->argument('source'),
                    $this->argument('entity'),
                    $id,
                    $this->argument('direction'),
                    $this->argument('source')
                ));
            }
        }
    }
}
