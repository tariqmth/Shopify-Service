<?php

namespace App\Queues\Jobs;

use App\Exceptions\ImpossibleTaskException;
use App\Models\Notification\ShopifyWebhookNotificationHandler;
use App\Models\Store\ShopifyStore;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ProcessShopifyWebhookNotification implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandleJobExceptions;

    protected $domainName;
    protected $topic;
    protected $notificationBody;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        $domainName,
        $topic,
        $notificationBody
    ) {
        $this->domainName = $domainName;
        $this->topic = $topic;
        $this->notificationBody = $notificationBody;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ShopifyWebhookNotificationHandler $notificationHandler)
    {
        try {
            $notificationHandler->process($this->domainName, $this->topic, $this->notificationBody);
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
        return null;
    }

    public function getEntityExternalId()
    {
        return null;
    }

    public function getDirection()
    {
        return 'out';
    }

    public function getClientId()
    {
        return null;
    }

    public function getShopifyStoreId()
    {
        try {
            $subdomain = strtok($this->domainName, '.');
            $shopifyStore = ShopifyStore::where('subdomain', $subdomain)->firstOrFail();
            return $shopifyStore->id;
        } catch (\Exception $e) {
            return null;
        }
    }
}
