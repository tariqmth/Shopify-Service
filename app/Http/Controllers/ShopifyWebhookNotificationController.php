<?php

namespace App\Http\Controllers;

use App\Queues\Jobs\ProcessShopifyWebhookNotification;
use Illuminate\Http\Request;

class ShopifyWebhookNotificationController extends Controller
{
    public function post(Request $request)
    {
        $domain = $request->header('X-Shopify-Shop-Domain');
        if (!isset($domain)) {
            throw new \Exception('No Shopify domain specified.');
        }

        $topic = $request->header('X-Shopify-Topic');
        if (!isset($topic)) {
            throw new \Exception('No Shopify webhook topic specified.');
        }

        $notificationBody = $request->getContent();

        ProcessShopifyWebhookNotification::dispatch($domain, $topic, $notificationBody)
            ->onConnection('database_sync')
            ->onQueue('notification')
            ->delay(now()->addMinutes(1));
    }
}
