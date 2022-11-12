<?php

namespace App\Http\Controllers;

use App\Models\Inventory\RexInventoryBufferGroup;
use App\Models\Inventory\RexInventoryBufferGroupMapping;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Store\ShopifyStore;
use App\Models\Client\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Validator;
use App\Models\Syncer\RexProductSyncer;

use App\Models\Mapper\ShopifyInventoryItemMapper;
use App\Models\Mapper\ShopifyInventoryLevelMapper;
use App\Models\Product\RexProduct;
use App\Models\Product\ShopifyProduct;
use App\Models\Product\ShopifyProductRepository;
use App\Models\Product\RexProductRepository;
use App\Models\Mapper\ShopifyProductMapper;
use App\Models\Store\RexSalesChannel;
use App\Models\Syncer\SyncerRepository;
use App\Models\Attribute\AttributeRepository;
use App\Models\Syncer\ShopifyInventoryItemSyncer;
use App\Packages\SkylinkSdkFactory;

class RexInventoryBufferGroupController extends Controller
{
    protected $shopifyMapper;
    protected $syncerRepository;
    protected $productRepository;
    protected $shopifyProductRepository;
    protected $attributeRepository;
    protected $shopifyInventoryItemMapper;
    protected $shopifyInventoryLevelMapper;
    protected $shopifyInventoryItemSyncer;
    protected $skylinkSdkFactory;
    protected $rexProductSyncer;
    public function __construct(
        ShopifyProductMapper $shopifyProductMapper,
        SyncerRepository $syncerRepository,
        RexProductRepository $productRepository,
        ShopifyProductRepository $shopifyProductRepository,
        AttributeRepository $attributeRepository,
        ShopifyInventoryItemMapper $shopifyInventoryItemMapper,
        ShopifyInventoryLevelMapper $shopifyInventoryLevelMapper,
        ShopifyInventoryItemSyncer $shopifyInventoryItemSyncer,
        SkylinkSdkFactory $skylinkSdkFactory,
        RexProductSyncer $rexProductSyncer
    ) {
        $this->shopifyMapper = $shopifyProductMapper;
        $this->syncerRepository = $syncerRepository;
        $this->productRepository = $productRepository;
        $this->shopifyProductRepository = $shopifyProductRepository;
        $this->attributeRepository = $attributeRepository;
        $this->shopifyInventoryItemMapper = $shopifyInventoryItemMapper;
        $this->shopifyInventoryLevelMapper = $shopifyInventoryLevelMapper;
        $this->shopifyInventoryItemSyncer = $shopifyInventoryItemSyncer;
        $this->skylinkSdkFactory = $skylinkSdkFactory;
        $this->rexProductSyncer = $rexProductSyncer;
    }



    /**
     * Display all inventory buffer groups of the client.
     *
     * @return \Illuminate\Http\Response
     */
    public function all(Request $request, $clientId,$subdomain)
    {
         try {
            $shopifyStore = $this->findShopifyStore($clientId, $subdomain);
        } catch (ModelNotFoundException $e) {
            return response('Shopify store could not be found.', 404);
        }
        $bufferGroups = RexInventoryBufferGroup::where('rex_sales_channel_id',$shopifyStore['rex_sales_channel_id'])->get();
        
        //create response
        $results['inventory_buffers'] =[];
        foreach ($bufferGroups as $bufferGroup)
        {
            $bufferGroupMappings = $this->getProductTypeIds($bufferGroup->id); 
            if ($bufferGroupMappings !== false){
                $results['inventory_buffers'][] =[
                    "id"=>$bufferGroup->id,
                    "name"=>$bufferGroup->name,
                    "rex_product_type_ids"=> $bufferGroupMappings,
                    "quantity"=>$bufferGroup->quantity,
                ];
            }
        }
        if (empty($results['inventory_buffers']) )
        {
            return response('Buffer Group could not be found.', 404);

        }
        return response()->json($results);
    }

    public function post(Request $request, $clientId,$subdomain)
    {
        try {
            $shopifyStore = $this->findShopifyStore($clientId, $subdomain);
        } catch (ModelNotFoundException $e) {
            return response('Shopify store could not be found.', 404);
        }
        $data = json_decode($request->getContent(), true);

        Validator::make($data, [
            'name' => 'required',
            'rex_product_type_ids'=> 'required',
            'quantity' => 'required|numeric|min:0'
        ])->validate();

        if (!is_string($data['name'])){
            return response("Invalid Inventory Buffer Group Name format. Should be string", 422);            
        }

        if (strtolower(trim($data['name'])) === 'default') {
            return response("Inventory Buffer Group Name 'Default' can not be used.", 422);
        }

        // Convert product type ids to array
        $product_type_ids = is_array($data['rex_product_type_ids']) ? $data['rex_product_type_ids'] : explode(",", $data['rex_product_type_ids']);
        $bufferName = trim($data['name']);

        $result = $this->findProductTypeIds($product_type_ids,$shopifyStore);
        if ($result !== false) {
            return response("Product Type {$result} already assigned.", 422);
        }

        // Perform validation to check if buffer name already exist
        if ($this->findBufferName($bufferName,$shopifyStore)) {
            return response("Buffer group name {$bufferName} already exist.", 422);
        }

        $bufferGroup = new RexInventoryBufferGroup;
        $bufferGroup->name = $bufferName;
        $bufferGroup->rex_sales_channel_id = $shopifyStore->rex_sales_channel_id;
        $bufferGroup->quantity = $data['quantity'];
        try {
            $bufferGroup->save();
        } catch (\Exception $e) {
            return response($e->getMessage(), 422);
        }

        $bufferGroupId = $bufferGroup->id;

        // save buffer group mappings
        $addedResult = $this->saveProductTypeIds($bufferGroupId,$product_type_ids);

        // Resync Products when a new Custom Inventory Buffer is created
        // Sync all product matched with product type id assign to buffer group
        $this->rexProductSyncer->syncInventoryBufferGroup($bufferGroup->id, $bufferGroup->rex_sales_channel_id);

        return response("Inventory Buffer Group {$bufferGroupId} created successfully.", 200);;
    }

    public function put(Request $request, $clientId,$subdomain,$id)
    {
        try {
            $shopifyStore = $this->findShopifyStore($clientId, $subdomain);
        } catch (ModelNotFoundException $e) {
            return response('Shopify store could not be found.', 404);
        }
        try {
            $bufferGroup = RexInventoryBufferGroup::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response('Inventory Buffer Group Id '.$id.' could not be found.', 404);
        }

        $data = json_decode($request->getContent(), true);

        Validator::make($data, [
            'name' => 'required',
            'rex_product_type_ids'=> 'required',
            'quantity' => 'required|numeric|min:0'
        ])->validate();
        
        $newBufferName = trim($data['name']);
        if (!is_string($newBufferName)){
            return response("Invalid Inventory Buffer Group Name format. Should be string", 422);            
        }

        if (strtolower($newBufferName) === 'default') {
            return response("Inventory Buffer Group Name 'Default' can not be used.", 422);
        }

        // Perform validation to check if updated buffer name is not same
        // and not already assigned to another buffer group
        if ($this->findBufferName($newBufferName,$shopifyStore) &&
            $newBufferName !== $bufferGroup->name) {
            return response("Buffer name {$newBufferName} already exist.", 422);
        }        


        // Convert product type ids to array
        $product_type_ids = is_array($data['rex_product_type_ids']) ? $data['rex_product_type_ids'] : explode(",", $data['rex_product_type_ids']);


        //Update only if buffer name changed
        if ($bufferGroup->name !== $newBufferName){
            $bufferGroup->name = $newBufferName;
        }
        $quantity_changed =  $bufferGroup->quantity === $data['quantity'] ? false : true;

        $bufferGroup->quantity = $data['quantity'];

        try {
            $bufferGroup->save();

            // Perform validation to check if product type already exist in another buffer group of same store/saleschannel
            $result = $this->findProductTypeIdsNotInBufferGroup($product_type_ids,$bufferGroup->id,$shopifyStore);
            if ($result !== false) {
                return response("Product Type {$result} already assigned to another buffer group.", 422);
            }

            // save buffer group mappings and ProductTypeIds to Sync
            $addedOrDeletedProductTypeIds = $this->saveProductTypeIds($bufferGroup->id,$product_type_ids);

            if ($quantity_changed)
            {
                // Sync all product matched with product type id assign to buffer group
                $this->rexProductSyncer->syncInventoryBufferGroup($bufferGroup->id, $bufferGroup->rex_sales_channel_id);
            }
            elseif ($quantity_changed === false && count($addedOrDeletedProductTypeIds)>0 ){
                // Sync only selected product type id
                $this->rexProductSyncer->syncInventoryBufferGroupByProductTypeId($bufferGroup->id, $bufferGroup->rex_sales_channel_id,$addedOrDeletedProductTypeIds);               
            }

        } catch (\Exception $e) {
            return response($e->getMessage(), 422);
        }

        return response("Inventory Buffer Group {$bufferGroup->id} updated successfully.", 200);;
    }
    public function delete(Request $request, $clientId,$subdomain,$id)
    {
        try {
            $shopifyStore = $this->findShopifyStore($clientId, $subdomain);
        } catch (ModelNotFoundException $e) {
            return response('Shopify store could not be found.', 404);
        }
        $bufferGroup = RexInventoryBufferGroup::find($id);
        if ($bufferGroup===null) {
            return response('Inventory Buffer Group Id '.$id.' could not be found.', 404);
        }

        try {
            // Before deleting make bufferquantity 0 and perform sync
            $bufferGroup->quantity = 0;
            $bufferGroup->save();

            // Sync all product matched with product type id assign to buffer group
            $this->rexProductSyncer->syncInventoryBufferGroup($bufferGroup->id, $bufferGroup->rex_sales_channel_id);

            // Delete all associated product type id first
            $this->deleteProductTypeIds($bufferGroup->id);

            // Permenantly delete inventory buffer group
            $bufferGroup->delete();

            return response('Inventory Buffer Group Id '.$id.' deleted successfully.', 200);            
        } catch (\Exception $e) {
            return response($e->getMessage(), 422);
        }
    }
    
    private function deleteProductTypeIds($bufferGroupId)
    {
        return RexInventoryBufferGroupMapping::where('group_id',$bufferGroupId)->delete();
    }
    private function deleteProductTypeId($productTypeId,$bufferGroupId)
    {
        return RexInventoryBufferGroupMapping::
            where('rex_product_type_id',$productTypeId)
            ->where('group_id',$bufferGroupId)
            ->delete();
    }

    private function saveProductTypeIds($bufferGroupId,$product_type_ids)
    {
        $product_type_ids_to_delete = [];
        $product_type_ids_added = [];

        // fetch all existing product type assigned to buffer group
        $existing_product_type_ids = $this->getProductTypeIds($bufferGroupId);

        // findout product type needs to deleted
        if ($existing_product_type_ids !== false)
        {
            $product_type_ids_to_delete = array_diff($existing_product_type_ids, $product_type_ids);
            if (count($product_type_ids_to_delete)>0)
            {
                foreach($product_type_ids_to_delete as $product_type_id)
                {
                    $this->deleteProductTypeId($product_type_id,$bufferGroupId);
                }
            }
        }

        foreach ($product_type_ids as $id) {
            // check if product type already exist
            if (!RexInventoryBufferGroupMapping::
                where('rex_product_type_id',$id)
                ->where('group_id',$bufferGroupId)
                ->exists())
            {
                $GroupMapping = new RexInventoryBufferGroupMapping;
                $GroupMapping->group_id = $bufferGroupId;
                $GroupMapping->rex_product_type_id = $id;
                $GroupMapping->save();
                $product_type_ids_added[]=$id;
            }                        
        }

        return array_merge($product_type_ids_to_delete,$product_type_ids_added);
    }
   
    private function getProductTypeIds($bufferGroupId)
    {
        $result = RexInventoryBufferGroupMapping::
            where('group_id',$bufferGroupId)
            ->pluck('rex_product_type_id')
            ->toArray();

        if (count($result)>0) {
            return $result;
        }
        return false;
    }

    private function findBufferName($bufferName, $shopifyStore)
    {
        return RexInventoryBufferGroup::
            where('name',$bufferName)
            ->where('rex_sales_channel_id', $shopifyStore->rex_sales_channel_id)
            ->exists();        
    }

    private function findProductTypeIdsNotInBufferGroup($product_type_ids,$bufferGroupId,$shopifyStore)
    {

        // Get all buffer groups except bufferGroupId (being edited) with product type ids for shopifystore sales channel
        $buffer_group_ids= RexInventoryBufferGroup::
            where('rex_sales_channel_id', $shopifyStore->rex_sales_channel_id)
            ->where('id','!=',$bufferGroupId)
            ->pluck('id')
            ->toArray();
        $rex_product_type_ids = RexInventoryBufferGroupMapping::
            whereIn('group_id',$buffer_group_ids)
            ->pluck('rex_product_type_id')
            ->toArray();
        foreach ($product_type_ids as $id) {
            if (in_array($id,$rex_product_type_ids))
            {
                return $id;
            }
        }
        return false;
    }
    private function findProductTypeIds($product_type_ids,$shopifyStore)
    {
        // Get all buffer groups with product type ids for shopifystore sales channel
        $buffer_group_ids= RexInventoryBufferGroup::
            where('rex_sales_channel_id', $shopifyStore->rex_sales_channel_id)
            ->pluck('id')
            ->toArray();
        $rex_product_type_ids = RexInventoryBufferGroupMapping::
            whereIn('group_id',$buffer_group_ids)
            ->pluck('rex_product_type_id')
            ->toArray();
        foreach ($product_type_ids as $id) {
            if (in_array($id,$rex_product_type_ids))
            {
                return $id;
            }
        }
        return false;
    }

    private function findShopifyStore($clientId, $subdomain)
    {
        $client = Client::where('external_id', $clientId)->firstOrFail();
        $shopifyStore = ShopifyStore
            ::where('shopify_stores.client_id', $client->id)
            ->where('subdomain', $subdomain)
            ->firstOrFail();
        return $shopifyStore;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Inventory\RexInventoryBufferGroup  $rexInventoryBufferGroup
     * @return \Illuminate\Http\Response
     */
    public function show(RexInventoryBufferGroup $rexInventoryBufferGroup)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Inventory\RexInventoryBufferGroup  $rexInventoryBufferGroup
     * @return \Illuminate\Http\Response
     */
    public function edit(RexInventoryBufferGroup $rexInventoryBufferGroup)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Inventory\RexInventoryBufferGroup  $rexInventoryBufferGroup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RexInventoryBufferGroup $rexInventoryBufferGroup)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Inventory\RexInventoryBufferGroup  $rexInventoryBufferGroup
     * @return \Illuminate\Http\Response
     */
    public function destroy(RexInventoryBufferGroup $rexInventoryBufferGroup)
    {
        //
    }
}
