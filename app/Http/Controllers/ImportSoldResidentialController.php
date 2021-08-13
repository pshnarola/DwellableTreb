<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Treb;
use Illuminate\Http\Request;
use App\Models\VowResidentialProperty;
use App\Models\VowCommercialProperty;
use App\Models\VowCondoProperty;
use Illuminate\Support\Facades\DB;

class ImportSoldResidentialController extends Controller
{
	public function __construct()
    {
		
	}
	

	public function importSoldResidential()
	{
		ini_set("memory_limit", "-1");
        set_time_limit(0);
		$names_arr = [];
		$path = "/srv/users/treb/apps/treb/public/sold_data/residential";
		$files = scandir($path);
		foreach ($files as &$value) {
			$names_arr[] = $value;
		}
		$importedData = DB::table("imported_sold_data")->get()->pluck("file_name")->toArray();
		$importedData[] = ".";
		$importedData[] = "..";
		
		foreach ($names_arr as $key => $file_name) {
			if(!in_array($file_name,$importedData)){
				$import = $this->importFileToDB($file_name);
			}
			
		}
		dd("End Of Script");
	}

	public function importFileToDB($file_name){
		
		$csv_path = "/srv/users/treb/apps/treb/public/sold_data/residential/$file_name";
		$row1 = 1;
		$keyField =  'ml_num';
		$column_to_lowercase =$missing_col= [];
		
		$columns = "SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS  WHERE TABLE_SCHEMA = 'treb' AND TABLE_NAME = 'sold_residential_properties'";
		//$arr = DB::select($columns)->get()->pluck("COLUMN_NAME");

		$columns_array = DB::table("INFORMATION_SCHEMA.COLUMNS")->where("TABLE_SCHEMA",'treb')->where("TABLE_NAME",'sold_residential_properties')->	select("COLUMN_NAME",DB::Raw("lower(COLUMN_COMMENT) as col_comment"))->get()->pluck("col_comment","COLUMN_NAME")->toArray();
        #dd($columns_array);
		$missing_columns = array(	
									"addl_mo_fee" => "Additional Monthly Fees",
									"constr1_out" => "Exterior 1",
									"constr2_out" => "Exterior 2",
									"lse_terms" => "Lease Terms",
									"ml_num" => "MLS#",
									"oh_dt_stamp" => "OpenHouse Timestamp",
									"rltr" => "List Broker",
									"vtour_updt" => "Virtual Tour Update Date",
									"prop_mgmt" => "Property Management",
									"water_type" => "Water Type"
									#"photo_number_list"=> "Photo Number List"
								);
		$missing_columns = array_map("strtolower",$missing_columns);

		$columns_array  = array_merge($columns_array,$missing_columns);
		 $rev_arr = array_flip($columns_array);
		 $columns_arr = [];
		
		if (($handle = fopen($csv_path, "r")) !== FALSE) {
			
			while (($data = fgetcsv($handle, 0, ',', '"', '"')) !== FALSE) {	
				
				if ($row1 == 1 ) {
					foreach ($data as $key => $col) {
						#if(in_array($col,$columns_array)){
						if(in_array(strtolower($col),$columns_array)){
							if($col == "Type" && !in_array("type_own_srch",$column_to_lowercase)){
								$column_to_lowercase[] = "type_own_srch";
							}elseif($col == "Type" && !in_array("type_own_srch",$column_to_lowercase)){
								$column_to_lowercase[] = "type_own1_out";
							}else{
								$column_to_lowercase[] = $rev_arr[strtolower($col)];
							}	
						}else{
							$column_to_lowercase[] = strtolower(str_ireplace(" ","_",$col));
							$missing_col[] = $col;
						}
					}
					
					// Add missing columns in table
					if(!empty($missing_col)){
						$newly_added = $this->addMissingColumns($missing_col,'sold_residential_properties');
					}
					$columns_arr = $column_to_lowercase;
				}
				// dd(array_diff(array_keys($columns_array),$columns_arr));
				if ($row1 > 1) {
				// if ($row1 ==5) {
					
					$values_arr = array();
					$column_to_lowercase = $columns_arr;
					$duplicate_append = "";
					$k = 0;
					$duplicate_append .= " ON DUPLICATE KEY UPDATE ";
					$new_values = "";
					
					foreach ($data as $value) {
						//echo ' in each ...';
						$value = $this->mysql_escape($value);
						$value = str_replace('\"',  '\""',  $value);
						$value = preg_replace('/(\\\",)/', '\\ ",', $value);
						$value = preg_replace('/(\\\"("?),)/', ' ', $value);
						
						
						$new_values .= ',';
						
						

						if(empty($value)){
							
							// if(in_array($k,[4,45,208,209,12,13,20,21,40,59,69,89,91,93,94,99,111,114,122,124,142,144,148,150,154,160,162,166,168,172,174,178,180,184,186,190,192,196,198,202,204,211,212,227,242,247,268,269,270,271,272,273,275,277,279,281,285,265,245])){
							// 	$values_arr[] = 0;
							// }else{
								unset($column_to_lowercase[$k]);
							// }
							
						}else{
							$check_date = stripslashes($value);
							$date = date('Y-m-d H:i:s',strtotime($check_date));
						    if (date_parse_from_format('m/d/Y H:i:s A',$check_date)['error_count'] == 0 && $date != "1970-01-01 00:00:00") {
								$values_arr[] = date('Y-m-d H:i:s',strtotime($check_date));
							}else{
							    $values_arr[] = $value;
							}
						}

						try {
							
							if (isset($column_to_lowercase[$k]) && $column_to_lowercase[$k] != $keyField) {
								$duplicate_append .= $column_to_lowercase[$k];
								$duplicate_append .= "=";

								$check_date = stripslashes($value);
								$date = date('Y-m-d H:i:s',strtotime($check_date));
								if (date_parse_from_format('m/d/Y H:i:s A',$check_date)['error_count'] == 0 && $date != "1970-01-01 00:00:00") {
									$duplicate_append .= "'{$date}',";
								}else{
									$duplicate_append .= "'{$value}',";
								}
								
							}
						} catch (\Exception $e) {
							echo $e->getMessage();
							//logError($e, "Error occurred in import data script");
						}

						$k++;
                    }
					
					$duplicate_append = rtrim($duplicate_append, ',');
					$values = "'" . implode("','", $values_arr) . "'";
					$columns = "`" . implode("`,`", $column_to_lowercase) . "`";
					#dd(array_values($column_to_lowercase),$values_arr);
                    $new_values = rtrim($new_values, ',');		
		
					  $query = "INSERT INTO `sold_residential_properties` ($columns) values ($values) $duplicate_append";	
					 			
					try
					{
						DB::statement($query);
					}
					catch(\Exception $e)
					{
						
						echo "<pre>";
						echo "file_name".$file_name."<br>";
						echo "row no".$row1."<br>";
						echo $e->getMessage();

						// $arr = array_values($column_to_lowercase);
						// print_r($arr);
						// print_r($values_arr);
                    }
                   // dd("das");
					
				}
				$row1++;
				
			}
			
		}
		
		echo " END OF SCRIPT $file_name,  $row1 executed";
	  return DB::table("imported_sold_data")->insert(
			['file_name' => $file_name, 'rows_count' => $row1,"created_at"=>date("Y-m-d H:i:s")]
		);

	}
	
	public function mysql_escape($inp)
    {
        if (is_array($inp)) return array_map(__METHOD__, $inp);

        if (!empty($inp) && is_string($inp)) {
            return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a", "/", '\"'), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z', '\\/', '\""'), $inp);
        }
        return $inp;
	}
	
	public function addMissingColumns($missingCols,$table){
		$add = "";
		$new_arr = [];
		foreach ($missingCols as $key => $col) {
			$col_index = strtolower(str_ireplace(" ","_",$col));
			$new_arr[$col_index] = $col;

			
			$add .= " ADD `$col_index` varchar(255) NULL";

			if(count($missingCols) !=  $key+1){
				$add .= ", ";
			}
		}
		$alter_sql = "ALTER TABLE `$table`
						$add";
		DB::statement($alter_sql);
		return $new_arr;
	}

	
}
?>