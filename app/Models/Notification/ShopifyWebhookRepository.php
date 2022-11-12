<?php

namespace App\Models\Notification;

use App\Models\Store\ShopifyStore;
use Illuminate\Support\Facades\URL;

class ShopifyWebhookRepository
{
    const TOPICS = [
        'order_transactions/create',
        'orders/create',
        'customers/create',
        'app/uninstalled',
        'shop/update',
        'fulfillments/create'
    ];

    const API_RESOURCE = '/api/shopify_webhook_notifications';

    public function get($shopifyStoreId, $topic)
    {
        return ShopifyWebhook::where('shopify_store_id', $shopifyStoreId)->where('topic', $topic)->first();
    }

    public function getOrCreate($shopifyStoreId, $topic, $address = null)
    {
        if (!isset($address)) {
            $address = $this->getAddress();
        }
        $shopifyWebhook = $this->get($shopifyStoreId, $topic);
        if (!isset($shopifyWebhook)) {
            $shopifyWebhook = new ShopifyWebhook;
            $shopifyWebhook->shopify_store_id = $shopifyStoreId;
            $shopifyWebhook->topic = $topic;
        }
        $shopifyWebhook->address = $address;
        $shopifyWebhook->save();
        return $shopifyWebhook;
    }

    public function all($shopifyStoreId)
    {
        return ShopifyWebhook::where('shopify_store_id', $shopifyStoreId)->get();
    }

    public function updateUninstallation($shopifyStoreId)
    {
        $address = $this->getAddress();
        $webhook = $this->get($shopifyStoreId, 'app/uninstalled');
        if (!isset($webhook) || $webhook->address !== $address || !$webhook->hasBeenSynced()) {
            return $this->getOrCreate($shopifyStoreId, 'app/uninstalled', $address);
        }
    }

    public function updateAll($shopifyStoreId)
    {
        $unsyncedWebhooks = [];
        $address = $this->getAddress();
        foreach (self::TOPICS as $topic) {
            $matchingWebhook = $this->get($shopifyStoreId, $topic);
            if (!isset($matchingWebhook)
                || $matchingWebhook->address !== $address
                || !$matchingWebhook->hasBeenSynced()
            ) {
                $unsyncedWebhooks[] = $this->getOrCreate($shopifyStoreId, $topic, $address);
            }
        }
        return $unsyncedWebhooks;
    }

    private function getAddress()
    {
        return env('APP_URL') . self::API_RESOURCE;
    }
}