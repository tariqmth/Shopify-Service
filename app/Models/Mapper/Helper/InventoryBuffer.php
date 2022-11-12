<?php
namespace App\Models\Mapper\Helper;

use App\Models\Inventory\RexInventoryBufferGroupMapping;
use App\Models\Inventory\RexInventoryBufferGroup;
use App\Models\Store\ShopifyStore;
use Illuminate\Support\Facades\Log;


trait InventoryBuffer
{
    public function getInventoryBufferQty($product_type_id,$rex_sales_channel_id)
    {
            $inventory_buffer = 0;

            // Retrieve any custom inventory buffers that impact the product being synced
            $custom_product_type_buffer = null; 

            
            $bufferGroup = RexInventoryBufferGroup::whereHas('rexInventoryBufferGroupMappings', 
                function($q) use ($product_type_id)
            {
                $q->where('rex_product_type_id',$product_type_id);
            })
            ->where('rex_sales_channel_id',$rex_sales_channel_id)
            ->first();

            if ($bufferGroup != null){
                $custom_product_type_buffer = $bufferGroup->quantity;                  
            }

            // Use custom inventory buffer Override default store buffer if available 
            if ($custom_product_type_buffer !== null )
            {
                $inventory_buffer = $custom_product_type_buffer ;
            }
            else
            {
                // Retrieve the default inventory buffer from the shopify_stores table 
                $shopifyStore = ShopifyStore::select('inventory_buffer')->where('rex_sales_channel_id',$rex_sales_channel_id)->first();
                
                $default_store_buffer = $shopifyStore->inventory_buffer;

                $inventory_buffer = $default_store_buffer ;                
            }
        return $inventory_buffer;
    }
}