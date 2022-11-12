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

class RexInventoryBufferGroupApiTest extends TestCase
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
        $response = $this->json('GET',"/api/clients/{$this->testClientId}/shopify_stores/{$this->testDomain}/inventory_buffers");
        $content= $response->getContent();
        print_r($content);
        $this->assertTrue(!empty($content));
        $this->assertContains( 'inventory_buffer', $content );
    }
    public function testPostClientInventoryBuffersApi()
    {
        //  /clients/<clientid>/shopify_stores/<subdomain>/inventory_buffers
        $this->setupTestEnv();
        $payload =['name'=>'tariq new sports'.rand(),'rex_product_type_ids'=>[68],'quantity'=>11];

        $response = $this->json('POST',"/api/clients/{$this->testClientId}/shopify_stores/{$this->testDomain}/inventory_buffers",$payload);

        $content= $response->getContent();
        print_r($content);
        $this->assertTrue(!empty($content));
        $this->assertContains( 'created', $content );
    }
    public function testPutClientInventoryBuffersApiSameName()
    {
        //  /clients/<clientid>/shopify_stores/<subdomain>/inventory_buffers/id
        $this->setupTestEnv();
        $payload =['name'=>'test buffer259','rex_product_type_ids'=>[222,333,444],'quantity'=>111];

        $response = $this->json('PUT',"/api/clients/{$this->testClientId}/shopify_stores/{$this->testDomain}/inventory_buffers/20",$payload);

        $content= $response->getContent();
        print_r($content);
        $this->assertTrue(!empty($content));
        $this->assertContains( 'updated', $content );
    }

    public function testPutClientInventoryBuffersApi()
    {
        //  /clients/<clientid>/shopify_stores/<subdomain>/inventory_buffers/id
        $this->setupTestEnv();
        $payload =['name'=>'update buffer group'.rand(),'rex_product_type_ids'=>[68],'quantity'=>rand(1,1)];

        $response = $this->json('PUT',"/api/clients/{$this->testClientId}/shopify_stores/{$this->testDomain}/inventory_buffers/47",$payload);

        $content= $response->getContent();
        print_r($content);
        $this->assertTrue(!empty($content));
        $this->assertContains( 'updated', $content );
    }
    public function testDeleteClientInventoryBuffersApi()
    {
        //  /clients/<clientid>/shopify_stores/<subdomain>/inventory_buffers/id
        $this->setupTestEnv();
        $response = $this->json('DELETE',"/api/clients/{$this->testClientId}/shopify_stores/{$this->testDomain}/inventory_buffers/19");

        $content= $response->getContent();
        print_r($content);
        $this->assertTrue(!empty($content));
        $this->assertContains( 'deleted', $content );
    }
}
