<?php

namespace App\Models\Mapper;

use App\Models\Order\RexOrderItem;
use App\Models\Order\RexOrderProduct;
use App\Models\Order\ShopifyOrderPriceCalculator;
use App\Models\Product\ShopifyProduct;
use App\Models\Product\ShopifyProductVariant;
use RetailExpress\SkyLink\Sdk\Sales\Orders\Item as RexItemData;
use RetailExpress\SkyLink\Sdk\Sales\Orders\ItemId;
use App\Models\Mapper\Helper\SingleTaxRateFromMultiple;
use Illuminate\Support\Facades\Log;
use App\Models\Product\RexProduct;
use App\Models\Mapper\Helper\InventoryBuffer;

class RexOrderItemMapperFromShopify
{
    use SingleTaxRateFromMultiple;
    use InventoryBuffer;

    public function getMappedData(RexOrderProduct $rexOrderProduct, $shopifyOrderItemData)
    {
        $rexProduct = $rexOrderProduct->rexProduct;

        if (!$rexProduct->hasBeenSynced()) {
            throw new \Exception('Cannot map Rex order item for Rex product that has not been synced.');
        }

        $quantityFulfilled = $this->getQuantityFulfilled($shopifyOrderItemData);
        $taxRates          = $this->getTaxRates($shopifyOrderItemData);
        $taxRate           = $this->getTaxRate($taxRates);
        $price             = $this->getPrice($rexOrderProduct);
        $externalItemId    = $this->getExternalItemId($rexOrderProduct);
        $processingModeId  = $this->getProcessingModeId($rexOrderProduct,$quantityFulfilled);

        $rexOrderItemData = RexItemData::fromNative(
            $rexProduct->external_id,
            $shopifyOrderItemData->quantity,
            $quantityFulfilled,
            $price,
            $taxRate,
            $externalItemId,
            $processingModeId
        );

        return $rexOrderItemData;
    }

    protected function getProcessingModeId(RexOrderProduct $rexOrderProduct,$quantityFulfilled = 0)
    {
        //Check if the product associated with the RexOrderProduct record is enabled for preorder
        $rex_product = RexProduct::where('id',$rexOrderProduct->rex_product_id)->first();


        if (count($rex_product)>0 && $rex_product->preorder_product == 1)
        {
            $preorder_product = $rex_product->preorder_product;
            $available_qty = intval($rex_product->available_stock);
            $rex_product_type_id = $rex_product->rex_product_type_id;
            $rex_sales_channel_id = $rex_product->rex_sales_channel_id;

            $processingModeId = 2;
            
            // Stock Available number is bigger than Inventory Buffer 
            // should be standard order so return 1
            $inventory_buffer = $this->getInventoryBufferQty($rex_product_type_id,$rex_sales_channel_id);
            if (($available_qty - $inventory_buffer) > $quantityFulfilled)
            {
                $processingModeId = 1;
            }
        }
        else
        {
            $processingModeId = 1;
        }

        return $processingModeId;
    }

    protected function getTaxRates($shopifyOrderItemData)
    {
        if (!$shopifyOrderItemData->taxable) {
            return [];
        }

        $taxRates = [];
        foreach ($shopifyOrderItemData->tax_lines as $taxLine) {
            $taxRates[] = $taxLine->rate;
        }
        return $taxRates;
    }

    protected function getQuantityFulfilled($shopifyOrderItemData)
    {
        return $shopifyOrderItemData->quantity - $shopifyOrderItemData->fulfillable_quantity;
    }

    protected function getPrice(RexOrderProduct $rexOrderProduct)
    {
        return $rexOrderProduct->price;
    }

    protected function getExternalItemId(RexOrderProduct $rexOrderProduct)
    {
        return $rexOrderProduct->id;
    }
}