<?php

use Illuminate\Database\Seeder;
use App\Models\Source;

class SourcesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('sources')->delete();
        Source::create(['id' => 1, 'name' => 'Rex']);
        Source::create(['id' => 2, 'name' => 'Shopify']);
    }
}