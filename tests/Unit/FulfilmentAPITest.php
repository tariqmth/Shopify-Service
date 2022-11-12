<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Setting\ClickAndCollectSetting; 
use App\Models\Apis\Retailexpress\AuthenticationAPI;
use App\Models\Apis\Retailexpress\FulfilmentAPI;

class FulfilmentAPITest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $this->assertTrue(true);
//  // a5c522ec-defa-4dfb-8904-0fa62dc47e94 client key dev-tariq

 //       $auth_api = new \App\Models\Apis\Retailexpress\AuthenticationAPI(497) ;// sales channel id tariq
 //      echo $auth_api->get_access_token();

        //$this->assertNotEmpty($auth_api->get_access_token());

        $fulfilment_api = new \App\Models\Apis\Retailexpress\FulfilmentAPI(115); // outlet id
        echo '==============rex get_access_token =============';
        echo $fulfilment_api->get_retailexpress_access_token();
        echo '==============shippit api key =============';
        echo $fulfilment_api->get_shippit_api_key();
        echo " ** FINISHED ** ";
    }
}
