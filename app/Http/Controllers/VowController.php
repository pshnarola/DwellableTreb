<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Treb;
use App\Models\LatLong;
use Illuminate\Http\Request;
use App\Models\VowResidentialProperty;
use App\Models\VowCommercialProperty;
use App\Models\VowCondoProperty;
use DB;

class VowController extends Controller
{
	protected $rets;
    public function __construct()
    {
		ini_set("memory_limit", "-1");
		set_time_limit(0);
		
        $config = new \PHRETS\Configuration;
        $config->setLoginUrl(env('VOW_LOGIN_URL'));
        $config->setUsername(env('VOW_USERNAME'));
        $config->setPassword(env('VOW_PASSWORD'));
        $rets = new \PHRETS\Session($config);
        $this->rets = $rets;
    }
	
	private function getMeta($resource, $class)
    {
        $connect = $this->rets->Login();
        $meta = $this->rets->GetTableMetadata($resource, $class)->toArray();
        $this->rets->Disconnect();
        return $meta;
    }
	
	public function createResidentialTable()
    {
        Treb::createTable($this->getMeta('Property', 'ResidentialProperty'), 'vow_residential_properties');
    }

    public function createCommercialTable()
    {
        Treb::createTable($this->getMeta('Property', 'CommercialProperty'), 'vow_commercial_properties');
    }
	
    public function createCondoTable()
    {
        Treb::createTable($this->getMeta('Property', 'CondoProperty'), 'vow_condo_properties');
    }
	
	public function downloadResidential()
    {
		$this->makeDownloadRequest('ResidentialProperty');
    }
	
	public function downloadCommerial()
    {
        $this->makeDownloadRequest('CommercialProperty');
    }
	
	public function downloadCondo()
    {
       $this->makeDownloadRequest('CondoProperty');
    } 

	private function makeDownloadRequest($className){ 
		$query = '(timestamp_sql=2020-02-18-2020-02-21)';  
        $connect = $this->rets->Login();
        $results = $this->rets->Search('Property', $className, $query);
        $records_found = $results->getTotalResultsCount();

        if ($records_found > 0) {
            $PropertyData = $results->toArray();

            foreach ($PropertyData as $Data) {
                $lowecase_key_data = array_change_key_case($Data, CASE_LOWER);
                $filtered = array_filter($lowecase_key_data, function ($value) {return !is_null($value) && $value !== '';});
				
				if($className == 'ResidentialProperty'){
					 $added = VowResidentialProperty::updateOrCreate(
                    ['ml_num' => $filtered['ml_num']],
                    $filtered
					);
				}
				
				if($className == 'CondoProperty'){
					 $added = VowCondoProperty::updateOrCreate(
                    ['ml_num' => $filtered['ml_num']],
                    $filtered
					);
				}
				
				//TODO OTHER
               

            }
        }
        \Log::info("Downloading ResidentialProperty completed. Records found $records_found ");
		echo 'done';
    }

    public function resiObject()
    {
        Treb::downloadObjects($this->rets, 'vow_residential_properties');
    }

    public function comObject()
    {
        Treb::downloadObjects($this->rets, 'vow_commercial_properties');
    }

    public function condoObject()
    {
        Treb::downloadObjects($this->rets, 'vow_condo_properties');
    }

    public function resiUpdate(){
        
        $latLong = new LatLong;
    	$maxTime = VowResidentialProperty::max('timestamp_sql');
		$className = "ResidentialProperty";
    	$query = "(timestamp_sql=$maxTime+)";  
    	
    	$connect = $this->rets->Login();
        $results = $this->rets->Search('Property', $className, $query);
        $records_found = $results->getTotalResultsCount();

        if ($records_found > 0) {
            $PropertyData = $results->toArray();

            foreach ($PropertyData as $Data) {
                $lowecase_key_data = array_change_key_case($Data, CASE_LOWER);
                $filtered = array_filter($lowecase_key_data, function ($value) {return !is_null($value) && $value !== '';});
				
				$added = VowResidentialProperty::updateOrCreate(
                    ['ml_num' => $filtered['ml_num']],
                    $filtered
                    );

                $ml_num   = $filtered['ml_num'];
                $active_exists = VowResidentialProperty::where('status','A')->where('lsc','New')->where('ml_num',$ml_num)->exists();
                if($active_exists){
                    $lat_long = $latLong->downloadLatLongByMlNum($ml_num,'vow_residential_properties');
                }
                
                //Check if images already exists
                $objects_exists = DB::table("objects")->where('ml_num',$ml_num)->exists();
                if(!$objects_exists){
                    Treb::downloadObjectsByMlNum($this->rets, $ml_num);
                }       
                    
            }
        }
        
        \Log::info("Downloading completed. Records found $records_found ");
		echo 'done';
    }

    public function condoUpdate(){
        $latLong = new LatLong;
    	$maxTime = VowCondoProperty::max('timestamp_sql');
		$className = "CondoProperty";
    	$query = "(timestamp_sql=$maxTime+)";  
    	
    	$connect = $this->rets->Login();
        $results = $this->rets->Search('Property', $className, $query);
        $records_found = $results->getTotalResultsCount();

        if ($records_found > 0) {
            $PropertyData = $results->toArray();

            foreach ($PropertyData as $Data) {
                $lowecase_key_data = array_change_key_case($Data, CASE_LOWER);
                $filtered = array_filter($lowecase_key_data, function ($value) {return !is_null($value) && $value !== '';});
				
				 $added = VowCondoProperty::updateOrCreate(
                    ['ml_num' => $filtered['ml_num']],
                    $filtered
                    );

                $ml_num   = $filtered['ml_num'];
                $active_exists = VowCondoProperty::where('status','A')->where('lsc','New')->where('ml_num',$ml_num)->exists();
                if($active_exists){
                    $lat_long = $latLong->downloadLatLongByMlNum($ml_num,'vow_condo_properties');
                }
                
                //Check if images already exists
                $objects_exists = DB::table("objects")->where('ml_num',$ml_num)->exists();
                if(!$objects_exists){
                    Treb::downloadObjectsByMlNum($this->rets, $ml_num);
                }       
            }
        }
        \Log::info("Downloading completed. Records found $records_found ");
		echo 'done';
    }

    public function searchMls($mlsid,$type)
    {
        print_r("Query: " . "(ml_num=$mlsid)");
        $connect = $this->rets->Login();
        $results = $this->rets->Search('Property', $type, "(ml_num=$mlsid)");
        echo '<pre>';
        echo "Total Records Count: " . $results->getTotalResultsCount();
        echo "<hr> Items: ";
        print_r($results->toArray());
    }

    public function updatePhoto($ml_num){
        //W4733078
        $connect = $this->rets->Login();
        Treb::downloadObjectsByMlNum($this->rets, $ml_num);
    }

    public function updateSoldPhoto($table,$ml_num){
        if($table=='condo'){
            $table_name = 'sold_condo_properties';
        }elseif($table=='freehold'){
            $table_name = 'sold_residential_properties';
        }else{
            echo "invalid table";
            die;
        }
        Treb::getArchivePhoto($ml_num,'sold_condo_properties');
    }
    private function switchUserForAvailableData()
    {
        $config = new \PHRETS\Configuration;
        $config->setLoginUrl(env('RETS_LOGIN_URL'));
        $config->setUsername(env('RETS_USERNAME') . "_a");
        $config->setPassword(env('RETS_PASSWORD'));
        $rets = new \PHRETS\Session($config);
        $this->rets = $rets;
    }

    public function removeCondoDeletedRows(){
        $className = 'CondoProperty';
        $query = '(Status=|A)';
        $keyField = "Ml_num";
        $this->switchUserForAvailableData();
        $connect = $this->rets->Login();
        $results = $this->rets->Search('Property', $className, $query,array('Select' => $keyField));

        $available_mls_in_rets = array_column($results->toArray(),'Ml_num');
        $existings_mls_in_db = DB::table('vow_condo_properties')->where('status','A')->get()->pluck('ml_num')->toArray();

        $diff = array_diff($existings_mls_in_db, $available_mls_in_rets);
        
        foreach ($diff as $ml_num) {
            DB::table('vow_condo_properties')->where('ml_num', $ml_num)->delete();
            \Log::info("Delete completed. Condo Records deleted $ml_num ");
            
        }
        die("End of script");
    }

    public function removeResidentialDeletedRows(){
        $className = 'ResidentialProperty';
        $query = '(Status=|A)';
        $keyField = "Ml_num";
        $this->switchUserForAvailableData();
        $connect = $this->rets->Login();
        $results = $this->rets->Search('Property', $className, $query,array('Select' => $keyField));

        $available_mls_in_rets = array_column($results->toArray(),'Ml_num');
        $existings_mls_in_db = DB::table('vow_residential_properties')->where('status','A')->get()->pluck('ml_num')->toArray();

        $diff = array_diff($existings_mls_in_db, $available_mls_in_rets);
       
        foreach ($diff as $ml_num) {
            DB::table('vow_residential_properties')->where('ml_num', $ml_num)->delete();
            \Log::info("Delete completed. ResidentialProperty Records deleted $ml_num ");
           
        }
        die("End of script");
    }
}
