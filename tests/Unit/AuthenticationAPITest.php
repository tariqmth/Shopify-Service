<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Setting\ClickAndCollectSetting; 
use App\Models\Apis\Retailexpress\AuthenticationAPI;
class AuthenticationAPITest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $this->assertTrue(true);
        $auth_api = new \App\Models\Apis\Retailexpress\AuthenticationAPI(497) ;// sales channel id tariq
        echo $auth_api->get_access_token();
        $this->assertNotEmpty($auth_api->get_access_token());
    }
}
