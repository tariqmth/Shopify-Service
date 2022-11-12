<?php

namespace App\Models\Syncer;

use App\Models\Attribute\AttributeRepository;
use App\Models\Attribute\RexAttributeOption;
use App\Models\Client\Client;
use App\Models\Product\RexProduct;
use App\Packages\SkylinkSdkFactory;
use App\Queues\Jobs\SyncRexAttributeOptionOut;
use Illuminate\Support\Facades\DB;
use RetailExpress\SkyLink\Sdk\Catalogue\Attributes\V2AttributeRepository as RexAttributeClient;
use RetailExpress\SkyLink\Sdk\Catalogue\Attributes\AttributeCode;

class RexAttributeOptionSyncer extends RexSyncer
{
    private $attributeRepository;
    private $skylinkSdkFactory;
    private $rexProductSyncer;

    public function __construct(
        AttributeRepository $attributeRepository,
        SkylinkSdkFactory $skylinkSdkFactory,
        RexProductSyncer $rexProductSyncer
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->skylinkSdkFactory = $skylinkSdkFactory;
        $this->rexProductSyncer = $rexProductSyncer;
    }

    public function syncOut(RexAttributeOption $option)
    {
        if (!$this->jobExists($option->id)) {
            SyncRexAttributeOptionOut::dispatch($option)
                ->onConnection('database_sync')
                ->onQueue('product_option');
        }
    }

    public function performSyncOut($optionId)
    {
        $option = RexAttributeOption::findOrFail($optionId);
        $client = $option->rexAttribute->client;
        foreach ($client->rexSalesChannels as $rexSalesChannel) {
            $this->limitApiCalls($client);
            $optionData = $this->fetchAttributeOptionData($option, $rexSalesChannel->external_id);
            if (!isset($optionData)) {
                continue;
            }
            $this->attributeRepository->createAttributeOptionFromData($client->id, $optionData);
            $syncedProductGroupIds = [];
            foreach ($optionData->getProductIds() as $productId) {
                $rexProduct = RexProduct
                    ::where('external_id', $productId->toNative())
                    ->where('rex_sales_channel_id', $rexSalesChannel->id)
                    ->first();
                if (isset($rexProduct)) {
                    if (isset($rexProduct->rexProductGroup)) {
                        if (in_array($rexProduct->rex_product_group_id, $syncedProductGroupIds)) {
                            continue;
                        } else {
                            $syncedProductGroupIds[] = $rexProduct->rex_product_group_id;
                        }
                    }
                    $this->rexProductSyncer->syncOut($rexProduct);
                }
            }
        }
    }

    public function syncIn(RexAttributeOption $option)
    {
        // todo
    }

    private function jobExists($optionId)
    {
        $job = DB::table('sync_jobs')
            ->where('source', 'rex')
            ->where('queue', 'product_option')
            ->where('entity_id', $optionId)
            ->where('direction', 'out')
            ->first();
        return isset($job);
    }

    private function fetchAttributeOptionData(RexAttributeOption $option, $salesChannelExternalId)
    {
        $client = $option->rexAttribute->client;
        $attributeClient = $this->getAttributeClient($client);
        return $attributeClient->findOption(
            AttributeCode::fromNative($option->rexAttribute->name),
            $option->option_id,
            $salesChannelExternalId
        );
    }

    private function getAttributeClient(Client $client)
    {
        $api = $this->skylinkSdkFactory->getApi($client);
        return new RexAttributeClient($api);
    }
}
