<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;

class SyncJobsHistory extends Model
{
    public $timestamps = false;

    protected $table = 'sync_jobs_history';

    public function shopifyStore()
    {
        return $this->belongsTo('App\Models\Store\ShopifyStore');
    }

    public function client()
    {
        return $this->belongsTo('App\Models\Client\Client');
    }

    public function parent()
    {
        return $this->belongsTo('App\Models\Job\SyncJobsHistory', 'parent_unique_id', 'unique_id');
    }

    public function children()
    {
        return $this->hasMany('App\Models\Job\SyncJobsHistory', 'parent_unique_id', 'unique_id');
    }

    public function syncJobsLogs()
    {
        return $this->hasMany('App\Models\Job\SyncJobsLog', 'sync_jobs_history_unique_id', 'unique_id');
    }

    public function syncJobsStatus()
    {
        return $this->belongsTo('App\Models\Job\SyncJobsStatus', 'status_code', 'code');
    }

    public function getEntityAttribute()
    {
        $source = ucfirst($this->source);

        switch ($this->queue) {
            case 'customer':
                $class = 'App\Models\Customer\\' . $source . 'Customer';
                break;
            case 'order':
                $class = 'App\Models\Order\\' . $source . 'Order';
                break;
            case 'payment':
                if ($source === 'Shopify') {
                    $class = 'App\Models\Payment\ShopifyTransaction';
                } else {
                    $class = 'App\Models\Payment\\' . $source . 'Payment';
                }
                break;
            case 'product_enabler':
            case 'product':
                $class = 'App\Models\Product\\' . $source . 'Product';
                break;
            case 'product_option':
                if ($source === 'Rex') {
                    $class = 'App\Models\Attribute\RexAttributeOption';
                } elseif ($source === 'Shopify') {
                    $class = 'App\Models\Product\\' . $source . 'Product';
                }
                break;
            case 'product_inventory':
                $class = 'App\Models\Inventory\\' . $source . 'InventoryItem';
                break;
            case 'fulfillment':
                $class = 'App\Models\Fulfillment\\' . $source . 'Fulfillment';
                break;
            case 'sales_channel_customers':
            case 'sales_channel_products':
                $class = 'App\Models\Store\RexSalesChannel';
                break;
            case 'fulfillment_service':
                $class = 'App\Models\Location\\' . $source . 'FulfillmentService';
                break;
            case 'notification_service':
                $class = 'App\Models\Notification\\' . $source . 'Webhook';
                break;
        }

        if (!isset($class) || !class_exists($class)) {
            return null;
        }

        return $class::find($this->entity_id);
    }
}
