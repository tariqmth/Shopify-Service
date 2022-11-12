<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Syncer\ShopifyVoucherSyncer;
use App\Models\Voucher\ShopifyVoucher;
use App\Models\Voucher\ShopifyVoucherAdjustment;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SyncShopifyVoucherAdjustmentIn implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $shopifyVoucherAdjustmentId;
    protected $data;
    protected $syncer;
    protected $clientId;
    protected $shopifyStoreId;
    protected $entityExternalId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        ShopifyVoucherAdjustment $shopifyVoucherAdjustment,
        $shopifyVoucherAdjustmentData
    ) {
        $this->shopifyVoucherAdjustmentId = $shopifyVoucherAdjustment->id;
        $this->data = $shopifyVoucherAdjustmentData;
        $shopifyStore = $shopifyVoucherAdjustment->shopifyVoucher->shopifyStore;
        $this->shopifyStoreId = $shopifyStore->id;
        $this->clientId = $shopifyStore->rexSalesChannel->client_id;
        $this->entityExternalId = $shopifyVoucherAdjustment->external_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ShopifyVoucherSyncer $syncer)
    {
        try {
            $syncer->performSyncInAdjustment($this->shopifyVoucherAdjustmentId, $this->data);
        } catch (ImpossibleTaskException $e) {
            Log::error($e);
            $this->fail($e);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    public function getSource()
    {
        return 'shopify';
    }

    public function getEntityId()
    {
        return $this->shopifyVoucherAdjustmentId;
    }

    public function getEntityExternalId()
    {
        return $this->entityExternalId;
    }

    public function getDirection()
    {
        return 'in';
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getShopifyStoreId()
    {
        return $this->shopifyStoreId;
    }
}
