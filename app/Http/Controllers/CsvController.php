<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Treb;
use Illuminate\Http\Request;
use App\Models\VowResidentialProperty;
use App\Models\VowCommercialProperty;
use App\Models\VowCondoProperty;
use Illuminate\Support\Facades\DB;

class CsvController extends Controller
{
	public function __construct()
    {
		
	}
	
	public function createTable()
	{
		$csv_path = "/srv/users/treb/apps/treb/public/fields.csv";
		$row = 1;
		
		if (($handle = fopen($csv_path, "r")) !== FALSE) {
			while (($columns_array = fgetcsv($handle, 0, ',', '"', '"')) !== FALSE) {
				while ("" === end($columns_array)) {
					array_pop($columns_array);
				}
				
				$all_columns_csv =  $columns_array;
				
				$num = count($columns_array);
				
				for ($c = 0; $c < $num; $c++) {
					if ($row == 1) {
						$columns_array = array_map(
							function ($value) {
								return '`' . $value . '`';
							},
							$columns_array
						);
						$columns_array = array_map('strtolower', $columns_array);
						$columns = implode(",", $columns_array);
						break;
					}
				}
                $row++;
                break;
			}
			fclose($handle);
		}
		
		
		$row1 = 1;
		
		if (($handle = fopen($csv_path, "r")) !== FALSE) {
			
			$sql_make="";
			 
			while (($data = fgetcsv($handle, 0, ',', '"', '"')) !== FALSE) {
            $num = count($data);
				
			if ($row1 == 1) {
				$column_to_lowercase =  array_map('strtolower', $data);					
			}
			
			$sql_query = "CREATE TABLE sold_residential_listings (\n";
			
			if ($row1 > 1) {
				
				$values_arr = array();
				
				$cleaned_comment = addslashes($data[0]);				

				$sql_make.= "\t`" .strtolower($data[1]) . "` ";
				
				$data_type = $data[2];
				$size = $data[3];
				$precision = $data[4];
				
				if($data_type=='Character') /* Date decimal Text*/
				{
					 $sql_make .= "VARCHAR(" . $size . ")";
				}
				elseif ($data_type == "Character" && $size > 255) {
                
				$sql_make .= "TEXT";
				} 
				else if ($data_type == "decimal") 
				{
					$precision = !empty($precision) ? $precision : 0;
					$post_point = !empty($precision) ? $precision : 0;
					$sql_make .= "DECIMAL({$size},{$post_point})";
				}
				else if ($data_type == "Date" ) {
					
                $sql_make .= "DATETIME DEFAULT NULL";
				}
				else{
					 $sql_make .= "TEXT";
				}
				$sql_make .=  " COMMENT '" . $cleaned_comment . "',\n";
				$sql_query .= $sql_make;
				$sql_query .=  "PRIMARY KEY(`ml_num`) )";
			}
			$row1++;
						
			}
		}   
		
		echo $sql_query;  	
		
	}
	
	
	public function download_csv_files()
	{
		$array_of_dates = [];
		
		$listing_types = array('free','condo');		
		
		$url_array = array();
		
		for($i=2010; $i<=2018; $i++ ){
			
			for($j=1; $j<=12 ; $j++){
				if($j<10)
				{
					$j= '0'.$j;
				}
				
				$month = $i.$j;
				
				$url= "https://getfiles.torontomls.net/GetFiles/GetArchiveDataFile.ashx?user_code=AV19akr&password=36$28yz&archive_file=$month&class=condo";
				
				echo '<a href="'.$url.'"> Download  '.$month.'</a><br><br>';
				
				//$contents = file_get_contents($url);
				//file_put_contents("/srv/users/treb/apps/treb/public/sold_data/residential/$month.csv", $contents);
				
				
			}	
		}
		echo " END OF SCRIPT ";
	}
	
	
	
	public function import_sold_dataq()
	{
		$csv_path = "/srv/users/treb/apps/treb/public/sold_data/residential/ArchiveListings_Free_201001.csv";
		$row1 = 1;
		$keyField =  'ml_num';
		
		$columns = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS  WHERE TABLE_SCHEMA = 'treb' AND TABLE_NAME = 'sold_residential_properties'";
		$arr = DB::select($columns);
		$columns_array =  array();
		foreach($arr as $col)
		{
			$columns_array[]=$col->COLUMN_NAME;
		}
			
		
		$columns_array = array_map('strtolower', $columns_array);
        $columns = implode(",", $columns_array);
		
		
		if (($handle = fopen($csv_path, "r")) !== FALSE) {
			
			while (($data = fgetcsv($handle, 0, ',', '"', '"')) !== FALSE) {	
				
				if ($row1 == 1 ) {					
					$column_to_lowercase =  array_map('strtolower', $data);	
                }
				
				if ($row1 > 1) {
					
					$values_arr = array();
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
						if(empty($value)){
							$values_arr[] = NULL;
						}else{
							$values_arr[] = $value;
						}
						$new_values .= ',';
						
						try {
							if ($columns_array[$k] != $keyField) {
								$duplicate_append .= $columns_array[$k];
								$duplicate_append .= "=";
								$duplicate_append .= "'{$value}',";
							}
						} catch (\Exception $e) {
							echo $e->getMessage();
							//logError($e, "Error occurred in import data script");
						}

						$k++;
                    }
					
					$duplicate_append = rtrim($duplicate_append, ',');
					$values = "'" . implode("','", $values_arr) . "'";
                    $new_values = rtrim($new_values, ',');		

echo "<pre>";
print_r($duplicate_append);
print_r($values_arr);
echo "</pre>";			

die("fdsfer");	
					
					$query = "INSERT INTO sold_residential_properties ($columns) values ($values) $duplicate_append";				
				
					
					try
					{
						DB::statement($query);
					}
					catch(Exception $e)
					{
						echo $e->getMessage();
					}
					
				}
				$row1++;
			}
			
		}
		
		echo " END OF SCRIPT ";
	}

	public function import_sold_data()
	{
		ini_set("memory_limit", "-1");
        set_time_limit(0);
		$names_arr = [];
		$path = "/srv/users/treb/apps/treb/public/sold_data/condo";
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
		
		$csv_path = "/srv/users/treb/apps/treb/public/sold_data/condo/$file_name";
		$row1 = 1;
		$keyField =  'ml_num';
		$column_to_lowercase =$missing_col= [];
		
		$columns = "SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS  WHERE TABLE_SCHEMA = 'treb' AND TABLE_NAME = 'sold_condo_properties'";
		//$arr = DB::select($columns)->get()->pluck("COLUMN_NAME");

		$columns_array = DB::table("INFORMATION_SCHEMA.COLUMNS")->where("TABLE_SCHEMA",'treb')->where("TABLE_NAME",'sold_condo_properties')->	select("COLUMN_NAME",DB::Raw("lower(COLUMN_COMMENT) as col_comment"))->get()->pluck("col_comment","COLUMN_NAME")->toArray();

		$missing_columns = array(	
									"addl_mo_fee" => "Additional Monthly Fees",
									"alt_power1" => "Alternative Power1",
									"alt_power2" => "Alternative Power2",
									"com_coopb" => "Commission to CoOperating Brokerage",
									"constr1_out" => "Exterior 1",
									"constr2_out" => "Exterior 2",
									"easement_rest1" => "Easements/Restrictions1",
									"easement_rest2" => "Easements/Restrictions2",
									"easement_rest3" => "Easements/Restrictions3",
									"easement_rest4" => "Easements/Restrictions4",
									"ml_num" => "MLS#",
									"oh_dt_stamp" => "OpenHouse Timestamp",
									"rltr" => "List Broker",
									"vtour_updt" => "Virtual Tour Update Date",
									"water_type" => "Water Type",
									"all_inc"=>"All Inclusive Rental",
									"link_y_n"=> "Link (Y/N)",
									"link_comment"=> "Link Comment",
									"photo_number_list"=> "Photo Number List"
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
						$newly_added = $this->addMissingColumns($missing_col,'sold_condo_properties');
					}
					$columns_arr = $column_to_lowercase;
				}
				
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
					#dd($column_to_lowercase,$values_arr);
                    $new_values = rtrim($new_values, ',');		
		
					  $query = "INSERT INTO `sold_condo_properties` ($columns) values ($values) $duplicate_append";	
					 			
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

						$arr = array_values($column_to_lowercase);
						print_r($arr);
						print_r($values_arr);
					}
					
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