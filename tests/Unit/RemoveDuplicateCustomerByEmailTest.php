<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RemoveDuplicateCustomerByEmail extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $this->assertTrue(true);
          // Artisan::call("infyom:scaffold", ['name' => $request['name'], '--fieldsFile' => 'public/Product.json']);
          \Artisan::call("shopify-connector:remove-duplicate-customers-by-email",['email'=>'kallista_69@hotmail.com','saleschannel'=> 277]);

        $this->assertTrue(true);
    }
}
