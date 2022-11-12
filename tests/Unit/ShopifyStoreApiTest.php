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
class ShopifyStoreApiTest extends TestCase
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
      $this->testDomain             = 'testlegacy2';
    }
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testClientShopifyStoresApi()
    {
        ///api/clients/{{ShopifyClientId}}/shopify_stores
        $this->setupTestEnv();
        $response = $this->json('GET',"/api/clients/{$this->testClientId}/shopify_stores");
        $content= $response->getContent();
        print_r($content);
        $this->assertTrue(!empty($content));
        $this->assertContains( 'inventory_buffer', $content );
    }
    public function testClientShopifyStoresSubdomainApi()
    {
        ///api/clients/{{ShopifyClientId}}/shopify_stores/{{ShopifySubdomain}}
        $this->setupTestEnv();
        $response = $this->json('GET',"/api/clients/{$this->testClientId}/shopify_stores/{$this->testDomain}");
        $content= $response->getContent();
        print_r($content);
        $this->assertTrue(!empty($content));
        $this->assertContains( 'inventory_buffer', $content );
    }

    public function testShopifyStoresSubdomainApi()
    {
        // /api/shopify_stores/{{ShopifySubdomain}}
        $this->setupTestEnv();
        $response = $this->json('GET',"/api/shopify_stores/{$this->testDomain}");
        $content = $response->getContent();
        print_r($content);
        $this->assertTrue(!empty($content));
        $this->assertContains( 'inventory_buffer', $content );
    }
    public function testClientShopifyStoresPutApi()
    {
        // /api/clients/{{ShopifyClientId}}/shopify_stores/{{ShopifySubdomain}}
        $this->setupTestEnv();
        $payload = [
            'shopify_store'=>[
                'access_token' =>'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',
                'full_domain' => 'mth.com',
                'inventory_buffer' => 100,
                'preorders' => 2
                ]
        ];
        $response =  $this->json('PUT',"/api/clients/{$this->testClientId}/shopify_stores/{$this->testDomain}",$payload);
        $content = $response->getContent();
        print_r($content);
        $this->assertTrue(!empty($content));
    }
    public function testShopifyStoresFind()
    {
        $preOrder = ShopifyStore::where('id',304)->get()->pluck('preorders');
        if (count($preOrder) === 0){
            echo "\nShopify store not found.\n";
        }
        var_dump($preOrder);
        $preOrder = $preOrder[0];
        echo "\n result of preorder value of store \n";
        print_r($preOrder);
        echo "\n ================================= \n";
        $this->assertTrue(!empty($preOrder));

    }    
}
