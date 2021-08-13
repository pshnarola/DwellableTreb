<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class LatLong extends Model
{
    protected $table = 'lat_longs';
    protected $fillable = ['ml_num', 'longitude', 'latitude', 'table_type'];


    function downloadLatLongByMlNum($ml_num,$table_name){
        
        //Check if lat_long exists
        $latLongExists = self::where('ml_num',$ml_num)->exists();
        if(!$latLongExists){
            $apikey = env('GOOGLE_MAP_APIKEY');
            $table_type = "";
            if($table_name == 'vow_residential_properties'){
                $table_type = 'residential';
            }elseif($table_name=='vow_condo_properties'){
                $table_type = 'condo';
            }

            $raw_query = "Select property_table.ml_num,property_table.addr,property_table.municipality,property_table.zip,property_table.county,property_table.area from $table_name as property_table where property_table.ml_num ='$ml_num' and property_table.area in ('Toronto','York','Durham','Halton','Peel') ";
            $results = DB::select(DB::raw($raw_query));
            
            foreach($results as $property) {
        
                $add = $property->addr;
                $municipality = $property->municipality;
                $zip = $property->zip;
                $county = $property->county;
                $area = $property->area;
                $ml_num= $property->ml_num;
    
                $full_address =  $add.' '.$municipality.' '.$area.' '.$county.' '.$zip;
                
                //Formatted address
                $formattedAddr = $full_address;
                
                $url = "https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($formattedAddr)."&key=".$apikey."";
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    
                $responseJson = curl_exec($ch);
                curl_close($ch);
                $api_response = json_decode($responseJson);
                $latitude = '';
                $longitude = '';
    
                /** Check if api responce is exit or not ***/
                if ($api_response->status == 'OK') {
                    $latitude = $api_response->results[0]->geometry->location->lat;
                    $longitude = $api_response->results[0]->geometry->location->lng;
                    
                } elseif($api_response->status == 'ZERO_RESULTS') {
                    $latitude = '';
                    $longitude = '';
                }
                
                try {
                    //insert query for lat long
                    $latLongObj = new LatLong();
                    $latLongObj->ml_num = $ml_num;
                    $latLongObj->longitude = !empty($longitude)?$longitude:0.00;
                    $latLongObj->latitude = !empty($latitude)?$latitude:0.00;
                    $latLongObj->table_type = $table_type;
                    \Log::info("Downloaded latLongs for $ml_num  ");
                    \Log::info("Api url for $ml_num  is $url");
                    \Log::info("Full Address for $ml_num  is $full_address and lat=$latitude and lng=$longitude");
                    return $latLongObj->save() ;
                
                } catch (Exception $e) {
                    \Log::info("Downloaded latLongs failed $ml_num  ");
                }
               
            }
        }
        return false;
    }

}