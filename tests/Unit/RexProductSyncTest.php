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
use App\Models\Store\ShopifyStore;
class RexProductSyncTest extends TestCase
{
    protected $testShopifyDomain;
    protected $testShopifyApiVersion;
    protected $shopifyInventoryItemSyncer;
    protected $shopifyProductRepository;
    protected $rexProductRepository;
    protected $RexSyncer;
    protected $testClientId;
    public function setupTestEnv()
    {
      $this->testDomain             = 'dev-tariq';
      $this->testShopifyApiVersion  = '2019-10';
      $this->testClientId             = 'a5c522ec-defa-4dfb-8904-0fa62dc47e94';
      // api-tst environment
      $this->testClientId ='fb6547cd-dd9f-472a-9215-5324d751ae61';
      $this->testDomain             = 'linhp01';
//      $this->testDomain             = 'nndev2021'; no store empty result

    }
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testClientInventoryBuffersApi()
    {
        //  /clients/<clientid>/shopify_stores/<subdomain>/inventory_buffers
        $this->setupTestEnv();
$preorder = ShopifyStore::where('id',241)->pluck('preorders');
if (count($preorder)===0)
echo "not found";

return;
        $rexProductSyncer = new RexProductSyncer(
        new ShopifyProductMapper,
        new SyncerRepository,
        new RexProductRepository,
        new ShopifyProductRepository,
        new AttributeRepository ,
        new ShopifyInventoryItemMapper,
        new ShopifyInventoryLevelMapper,
        new ShopifyInventoryItemSyncer ,
        new SkylinkSdkFactory
        );
        $rexProductSyncer->performSyncOut(130960);
        print_r($content);
        $this->assertTrue(!empty($content));
        $this->assertContains( 'inventory_buffer', $content );
    }
}
