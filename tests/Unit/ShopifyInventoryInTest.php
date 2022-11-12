<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Syncer\RexORderSyncer;
use App\Models\Order\ShopifyOrder;
use Illuminate\Http\Request;
use App\Packages\ShopifySdkFactory;
use App\Models\Syncer\ShopifyOrderSyncer;
use Illuminate\Support\Facades\Log;
use App\Models\Order\RexOrder;
use App\Queues\Jobs\SyncShopifyOrderOut;
use App\Queues\Jobs\SyncRexOrderIn;
use App\Exceptions\ExternalEntityNotFoundException;
use App\Exceptions\ImpossibleTaskException;
use App\Models\Syncer\ShopifyInventoryItemSyncer;
use App\Models\Product\ShopifyProductRepository;
use App\Models\Product\RexProductRepository;
use App\Models\Mapper\ShopifyProductMapper;
use App\Models\Product\RexProduct;
use App\Models\Syncer\RexSyncer;
use App\Models\Syncer\RexProductSyncer;
use App\Models\Mapper\ShopifyInventoryItemMapper;
use App\Models\Inventory\ShopifyInventoryItem;
use App\Queues\Jobs\SyncShopifyInventoryLevelIn;

class ShopifyInventoryInTest extends TestCase
{
    protected $testShopifyDomain;
    protected $testShopifyApiVersion;
    protected $shopifyInventoryItemSyncer;
    protected $shopifyProductRepository;
    protected $rexProductRepository;
    protected $RexSyncer;
    public function setupTestEnv()
    {
      $this->testDomain             = 'dev-tariq.myshopify.com';
      $this->testShopifyApiVersion  = '2019-10';
//      $this->shopifyInventoryItemSyncer = new shopifyInventoryItemSyncer();
//      $this->shopifyProductRepository = new ShopifyProductRepository;
//      $this->rexProductRepository = new RexProductRepository;
//      $this->RexSyncer = new RexSyncer;

    }
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $this->assertTrue(true);
    }

    public function testInventoryInTest()
    {
        $this->setupTestEnv();
        $rexProduct = RexProduct::findOrFail(38202); // dev-tariq
        
        $shopifyInventoryItem = ShopifyInventoryItem::findOrFail(20772);
        
        $inventoryLevelData = ['location_id'=>'373','available'=>66];
        SyncShopifyInventoryLevelIn::dispatch($shopifyInventoryItem, $inventoryLevelData)
            ->onConnection('database_inventory_sync')
            ->onQueue('product_inventory');

//      $this->shopifyInventoryItemSyncer->syncIn($shopifyInventoryItem, $mappedInventoryItemData);
//      $this->shopifyInventoryItemSyncer->syncInLevel($shopifyInventoryItem, $mappedInventoryLevelData);

    }
}
