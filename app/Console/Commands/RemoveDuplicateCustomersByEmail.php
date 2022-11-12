<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer\ShopifyCustomerRepository;
use App\Models\Customer\RexCustomerRepository;
use App\Models\Customer\RexCustomer;
use App\Models\Customer\ShopifyCustomer;
use App\Models\Store\ShopifyStore;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\ConsoleOutput;
class RemoveDuplicateCustomersByEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify-connector:remove-duplicate-customers-by-email 
                            {email : customer email} 
                            {saleschannel : Indicates given name is rex SalesChannel}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'REMOVE DUPLICATE CUSTOMERS based on customer email';

    protected $rex_customers_ids_to_delete = [];
    protected $shopify_customers_ids_to_delete = [];

    protected $customer_email='';
    protected $output=false;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->output  = true;
        $this->customer_email = $this->argument('email');
        $saleschannel   = $this->argument('saleschannel');

        $shopifyStore = ShopifyStore::where('rex_sales_channel_id', $saleschannel)->firstOrFail();

        // Phase 1
        $this->RemoveEmptyExternalIds($shopifyStore->rex_sales_channel_id,$shopifyStore->id);

        // Phase 2
        $this->RemoveUnMappedRecords($shopifyStore->rex_sales_channel_id);

        // Phase 3
        $this->UpdateMissingRexCustomerIdsInShopifyCustomers($shopifyStore->id);
        
        // Phase 4
        $this->RemoveEmptyEmailAddresses($shopifyStore->rex_sales_channel_id,$shopifyStore->id);

    }
    private function RemoveEmptyEmailAddresses($rex_sales_channel_id,$shopify_store_id)
    {
        $rex_customers      = RexCustomer::where('rex_sales_channel_id',$rex_sales_channel_id)
                            ->whereNull('email')
                            ->get();
        $shopify_customers  = ShopifyCustomer::where('shopify_store_id',$shopify_store_id)
                            ->whereNull('email')
                            ->get();
        $this->Output_info(count($rex_customers) .' empty email addresses found in Rex Customers');
        $this->Output_info(count($shopify_customers) .' empty email addresses found in Shopify Customers');
        if (count($rex_customers)===0 && count($shopify_customers)===0)
        {
            return;
        }
       $affectedRows = 0;
       $affectedRows += RexCustomer::where('rex_sales_channel_id',$rex_sales_channel_id)
                        ->whereNull('email')
                        ->delete();
        $affectedRows += ShopifyCustomer::where('shopify_store_id',$shopify_store_id)
                        ->whereNull('email')
                        ->delete();
        $this->Output_info($affectedRows.' Records has been deleted!.');
    }
    private function UpdateMissingRexCustomerIdsInShopifyCustomers($store_id)
    {
        // Shopify Customer update missing rex_customer_id if email exist in both table
        // AND rex_customer_id doesn't already exist in shopify customer

        $this->Output_info('Getting data for shopify customers missing rex id...');
        $shopify_customers = $this->GetShopifyCustomersMissingRexCustomerId($store_id);
        $dc = count($shopify_customers); 
        if ($dc > 0)
        {
            $this->Output_info($dc. " email addresses found with missing Rex Id in Shopify Customer table");
            //$bar = $this->output->createProgressBar($dc);
            $affectedRows = 0;
            foreach ($shopify_customers as $customer) {
                if (!ShopifyCustomer::where('rex_customer_id',$customer->rex_customer_id)->exists())
                {
                    $affectedRows += ShopifyCustomer::where('id', '=', $customer->id)->
                                    update(array('rex_customer_id' => $customer->rex_customer_id));
                }
              //  $bar->advance();
            }
            //$bar->finish();
            $this->Output_info($affectedRows.' has been updated with rex_customer_id in Shopify Customer');            
        }
    }

    private function RemoveUnMappedRecords($rex_sales_channel_id)
    {

        // Rex Customer - Check if record exist in shopify delete unmapped record
        $duplicate_rex_customers = $this->GetDuplicateRexCustomers($rex_sales_channel_id);
        $dc = count($duplicate_rex_customers); 
        //$bar = $this->output->createProgressBar($dc);
        if ($dc > 0)
        {
            $this->Output_info($dc. " Duplicate email addresses found in Rex Customer table");
            foreach ($duplicate_rex_customers as $email) {
                $this->CheckRexCustomerIdInShopifyCustomer($email);
          //      $bar->advance();
            }
        }
        //$bar->finish();
        $this->Output_info(count($this->rex_customers_ids_to_delete).' added in Rex Customer Delete Queue');
//        $this->Output_info(print_r($this->rex_customers_ids_to_delete));
        
        if ($this->PerformDeletion()===false){
            exit('Aborted!.') ;
        }

    }
    private function RemoveEmptyExternalIds($rex_sales_channel_id,$store_id)
    {
        // Rex Customers - Remove customer having empty External Ids of duplicate email addresses
        $duplicate_rex_customers = $this->GetDuplicateRexCustomers($rex_sales_channel_id);
        $dc = count($duplicate_rex_customers); 
        //$bar = $this->output->createProgressBar($dc);
        if ($dc > 0)
        {
            $this->Output_info($dc. " Duplicate email addresses found in Rex Customer table");
            foreach ($duplicate_rex_customers as $email) {
                $this->RemoveEmptyExternalIdRexCustomers($email);
          //      $bar->advance();
            }
        }
        //$bar->finish();
        $this->Output_info(count($this->rex_customers_ids_to_delete).' added in Rex Customer Delete Queue');
        //

        // Rex Shopify - Remove customer having empty External Ids of duplicate email addresses
        $duplicate_shopify_customers = $this->GetDuplicateShopifyCustomers($store_id);
        $dc = count($duplicate_shopify_customers);
        //$bar = $this->output->createProgressBar($dc);
        if ($dc >0 )
        {
            $this->Output_info($dc. " Duplicate email addresses found in Shopify Customer table");
            foreach ($duplicate_shopify_customers as $email) {
                $this->RemoveEmptyExternalIdShopifyCustomers($email);
                $this->RemoveEmptyRexCustomerIdShopifyCustomers($email);           
          //      $bar->advance();
            }
        }
        //$bar->finish();
        $this->Output_info(count($this->shopify_customers_ids_to_delete).' added in Shopify Customer Delete Queue');
        //

        if ($this->PerformDeletion()===false){
            exit('Aborted!.'); ;
        }
    }
    private function GetShopifyCustomersMissingRexCustomerId($store_id)
    {
        // Actual SQL to retrieve missing rex_customer_id in shopify_customers
        // SELECT sc.id AS shopify_customer_id,rc.id AS rex_customer_id FROM shopify_customers sc INNER JOIN rex_customers // rc ON sc.`email`=rc.`email`
        // WHERE ISNULL(sc.rex_customer_id) AND shopify_store_id = 356;

        return DB::table('rex_customers')
                ->join('shopify_customers','shopify_customers.email','=','rex_customers.email')
                ->where('shopify_customers.shopify_store_id','=',$store_id)
                ->whereNull('shopify_customers.rex_customer_id')
                ->select('shopify_customers.id','rex_customers.id as rex_customer_id')
                ->get() ;
    }
    private function PerformDeletion()
    {
        if (count($this->rex_customers_ids_to_delete)===0 && count($this->shopify_customers_ids_to_delete)===0)
        {
            return;
        }
        // ** PERFORM DELETION ** //
        //if ($this->confirm('Going to Perform actual Deletion, Do you wish to continue?')) {
            $result_rex = RexCustomer::wherein('id',$this->rex_customers_ids_to_delete)->delete();
            $this->Output_info($result_rex. " Records has been deleted from Rex Customer");
            $result_shopify = ShopifyCustomer::wherein('id',$this->shopify_customers_ids_to_delete)->delete();            
            $this->Output_info($result_shopify. " Records has been deleted from Shopify Customer");
            $this->rex_customers_ids_to_delete=[];
            $this->shopify_customers_ids_to_delete=[];
            return true;
        //}
        //else
        //{
        //    $this->Output_info("Aborted !. No Deletion performed");
        //    return false;
        //}
        //
    }
    private function GetDuplicateRexCustomers($rex_sales_channel_id)
    {
        return DB::table('rex_customers')
            ->where('rex_sales_channel_id' , $rex_sales_channel_id)
            ->where('email',$this->customer_email)
            ->groupBy('email')
            ->having(DB::raw('count(email)'), '>', 1)
            ->pluck('email'); 
    }

    private function GetDuplicateShopifyCustomers($shopify_store_id)
    {
        return DB::table('shopify_customers')
            ->where('shopify_store_id' , $shopify_store_id)
            ->where('email',$this->customer_email)
            ->groupBy('email')
            ->having(DB::raw('count(email)'), '>', 1)
            ->pluck('email'); 
    }

    private function RemoveEmptyExternalIdRexCustomers($email)
    {
        $rex_customers = RexCustomer::where('email',$email)->get();
        foreach ($rex_customers as $customer) {
            //$this->Output_info($email.' checking for empty external id - '.$customer->external_id);
            if ($customer->external_id===null || $customer->external_id==='')
            {
                $this->rex_customers_ids_to_delete[] = $customer->id;
            }
            //check related record in shopify customer
            $shopify_customer = ShopifyCustomer::where('rex_customer_id',$customer->id)->first();
            if ($shopify_customer)
            {
                $this->shopify_customers_ids_to_delete[] = $shopify_customer->id; 
            }
        }
    }

    private function RemoveEmptyExternalIdShopifyCustomers($email)
    {
        $shopify_customers = ShopifyCustomer::where('email',$email)->get();
        foreach ($shopify_customers as $customer) {
            if ($customer->external_id===null || $customer->external_id==='')
            {
                $this->shopify_customers_ids_to_delete[] = $customer->id;
            }
        }
    }
    private function RemoveEmptyRexCustomerIdShopifyCustomers($email)
    {
        $shopify_customers = ShopifyCustomer::where('email',$email)->get();
        $rex_customer_id_exist=[];
        $rex_customer_id_doesnot_exist=[];
        foreach ($shopify_customers as $customer) {
            if ($customer->rex_customer_id==null || $customer->rex_customer_id=='')
            {
                $rex_customer_id_doesnot_exist[] = $customer->id;
            }else{
                $rex_customer_id_exist[] = $customer->id;
            }
        }
        // Keep one record with customer Id
        if (count($rex_customer_id_exist)>0)
        {
            $count_id = 0;
            foreach ($rex_customer_id_exist as $customer_id) {
                $count_id++;
                if ($count_id>1)
                {
                    $this->shopify_customers_ids_to_delete[] = $customer_id;
                }
            }
            foreach ($rex_customer_id_doesnot_exist as $customer_id) {
                $this->shopify_customers_ids_to_delete[] = $customer_id;                
            }
        }else{ // keep one record without id
            $count_id = 0;
            foreach ($rex_customer_id_doesnot_exist as $customer_id) {
                $count_id++;
                if ($count_id>1)
                {
                    $this->shopify_customers_ids_to_delete[] = $customer_id;
                }
            }
        }
    }

    private function CheckRexCustomerIdInShopifyCustomer($email)
    {
        $rex_customers = RexCustomer::where('email',$email)->get();
        //$this->Output_info($email.' checking for rex customer id in shopify');
        foreach ($rex_customers as $customer) {
            if (!$this->IsEmailExistInShopifyCustomer($customer->email))
            {
                $this->rex_customers_ids_to_delete[]=$customer->id;
            }

            if (!$this->IsRexCustomerIdInShopifyCustomerExist($customer->id))
            {
                $this->rex_customers_ids_to_delete[]=$customer->id;
            }
        }

    }
    private function IsEmailExistInShopifyCustomer($email)
    {
        return
            ShopifyCustomer::where('email', '=', $email)->exists();
    }
    private function IsRexCustomerIdInShopifyCustomerExist($rex_customer_id)
    {
        return
            ShopifyCustomer::where('rex_customer_id', '=', $rex_customer_id)->exists();
    }

    private function Output_info($text)
    {
        if ($this->output === false)
        {
            return;
        }
        $output = new ConsoleOutput();
        $output->writeln($text);
    }
}
