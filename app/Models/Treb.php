<?php

namespace App\Models;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\DB;

class Treb
{
    public static $failed_count = 0;
    public static function createTable($rets_metadata, $tablename)
    {
        $sql_query = "CREATE TABLE " . $tablename . " (\n";
        foreach ($rets_metadata as $field) {
            $long_name = $field->getLongName();
            $system_name = $field->getSystemName();
            $data_type = $field->getDataType();
            $max_length = $field->getMaximumLength();
            $interpretation = $field->getInterpretation();
            $precision = $field->getPrecision();

            $cleaned_comment = addslashes($long_name);
            $sql_make = "\t`" . strtolower($system_name) . "` ";
            if ($interpretation == "LookupMulti" || $interpretation == "lookupmulti") {
                $sql_make .= "TEXT";
            } elseif ($interpretation == "Lookup" || $interpretation == "lookup") {
                $sql_make .= "TEXT";
            } elseif ($data_type == "Int" || $data_type == "Small" || $data_type == "Tiny") {
                $sql_make .= "INT(" . $max_length . ")";
            } elseif ($data_type == "Long") {
                $sql_make .= "BIGINT(" . $max_length . ")";
            } elseif ($data_type == "DateTime") {
                $sql_make .= "DATETIME DEFAULT NULL";
            } elseif ($data_type == "Timestamp") {
                $sql_make .= "DATETIME DEFAULT NULL";
            } elseif ($data_type == "Character" && $max_length <= 255) {
                $sql_make .= "VARCHAR(" . ($max_length + 5) . ")";
            } elseif ($data_type == "Character" && $max_length > 255) {
                $sql_make .= "TEXT";
            } elseif ($data_type == "Decimal") {
                $precision = !empty($precision) ? $precision : 0;
                $post_point = !empty($precision) ? $precision : 0;
                $sql_make .= "DECIMAL(" . ($max_length + 2) . ",{$post_point})";
            } elseif ($data_type == "Boolean") {
                $sql_make .= "CHAR(1)";
            } elseif ($data_type == "Date") {
                $sql_make .= "DATE DEFAULT NULL";
            } elseif ($data_type == "Time") {
                $sql_make .= "TIME DEFAULT NULL ";
            } else {
                $sql_make .= "VARCHAR(255)";
            }
            $sql_make .= " COMMENT '" . $cleaned_comment . "',\n";
            $sql_query .= $sql_make;
        }
        $sql_query .= "PRIMARY KEY(`ml_num`) ) ENGINE=MyISAM ROW_FORMAT=COMPRESSED ";
        try {
            DB::statement($sql_query);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
        echo "createTable $tablename done\n";
        \Log::info("Table created for $tablename");
    }

    public static function updateTable($rets_metadata, $tablename)
    {
        $table_column_names = get_table_columns($tablename);
        $missing_fields = array();
        foreach ($rets_metadata as $field) {
            $long_name = $field->getLongName();
            $system_name = $field->getSystemName();
            $data_type = $field->getDataType();
            $max_length = $field->getMaximumLength();
            $interpretation = $field->getInterpretation();
            $precision = $field->getPrecision();
            $field_arr = array();
            $field_arr['long_name'] = $long_name;
            $field_arr['system_name'] = $system_name;
            $field_arr['data_type'] = $data_type;
            $field_arr['max_length'] = $max_length;
            $field_arr['interpretation'] = $interpretation;
            $field_arr['precision'] = $precision;
            $system_name = strtolower($system_name);
            if (!in_array($system_name, $table_column_names)) {
                $missing_fields[] = $field_arr;
            }
        }

        if (count($missing_fields) > 0) {
            foreach ($missing_fields as $field) {
                $sql_query = "ALTER TABLE " . $tablename . " ADD COLUMN \n";
                $cleaned_comment = addslashes($field['long_name']);
                $sql_make = "\t`" . strtolower($field['system_name']) . "` ";
                if ($field['interpretation'] == "LookupMulti") {
                    $sql_make .= "TEXT";
                } elseif ($field['interpretation'] == "Lookup") {
                    $sql_make .= "VARCHAR(50)";
                } elseif ($field['data_type'] == "Int" || $field['data_type'] == "Small" || $field['data_type'] == "Tiny") {
                    $sql_make .= "INT(" . $field['max_length'] . ")";
                } elseif ($field['data_type'] == "Long") {
                    $sql_make .= "BIGINT(" . $field['max_length'] . ")";
                } elseif ($field['data_type'] == "DateTime") {
                    $sql_make .= "DATETIME DEFAULT NULL";
                } elseif ($field['data_type'] == "Character" && $field['max_length'] <= 255) {
                    $sql_make .= "VARCHAR(" . ($field['max_length'] + 5) . ")";
                } elseif ($field['data_type'] == "Character" && $field['max_length'] > 255) {
                    $sql_make .= "TEXT";
                } elseif ($field['data_type'] == "Decimal") {
                    $field['precision'] = !empty($field['precision']) ? $field['precision'] : 0;
                    $post_point = !empty($field['precision']) ? $field['precision'] : 0;
                    $sql_make .= "DECIMAL(" . ($field['max_length'] + 2) . ",{$post_point})";
                } elseif ($field['data_type'] == "Boolean") {
                    $sql_make .= "CHAR(1)";
                } elseif ($field['data_type'] == "Date") {
                    $sql_make .= "DATE DEFAULT NULL";
                } elseif ($field['data_type'] == "Time") {
                    $sql_make .= "TIME DEFAULT NULL ";
                } else {
                    $sql_make .= "VARCHAR(255)";
                }
                $sql_make .= " COMMENT '" . $cleaned_comment . "';";
                $sql_query .= $sql_make;
                try {
                    DB::statement($sql_query);
                } catch (\Exception $e) {
                    dd($e->getMessage());
                }
            }
        }
        echo "updateTable $tablename done\n";
        \Log::info("Table updated for $tablename");
    }

    public static function removeRows($class_name, $table_name)
    {
        $table_name_available = 'property_available';
        try {
            DB::statement("SELECT 1 FROM  $table_name LIMIT 1");
        } catch (\Illuminate\Database\QueryException $e) {
            die($table_name . " dose not exists");
        }
        try {
            DB::statement("SELECT 1 FROM  $table_name_available LIMIT 1");
        } catch (\Illuminate\Database\QueryException $e) {
            die("Property avaliable table dose not exists");
        }

        $already_rows = DB::select("SELECT count(*) as count from $table_name_available where class_name='$class_name' ");
        if ($already_rows[0]->count == 0) {
            die('No avaliable records found for ' . $class_name);
        }

        $keyField = 'ml_num';
        $allKeys = DB::table($table_name)->select($keyField)->get();
        $allAvaliableKeys = DB::table($table_name_available)->select($keyField)->where('class_name', $class_name)->get();
        $keyList = [];
        $keyListAvaliable = [];
        foreach ($allKeys as $keyItem) {
            $keyList[] = (string) $keyItem->$keyField;
        }
        foreach ($allAvaliableKeys as $keyItem) {
            $keyListAvaliable[] = $keyItem->$keyField;
        }
        $diff = array_diff($keyList, $keyListAvaliable);
        $totalCount = count($diff);
        foreach ($diff as $item) {
            DB::table($table_name)->where($keyField, $item)->delete();
        }
        \Log::info("Removed extra rows for $table_name count" . $totalCount);
    }

    public static function downloadObjects($rets, $tablename)
    {
        
        $date = \Carbon\Carbon::today()->subDays(30);
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
        ]);
        $Bucketname = "dwellable-treb";
        $connect = $rets->Login();

        $listingsdata = DB::table($tablename)->select('ml_num')
        ->whereIn('area',['Toronto','Durham','York','Halton','Peel'])
        ->where('status',"U")
        #->whereBetween('cd', [date("2019-09-30"),date("2019-10-31")])
        ->orderBy('input_date','DESC')
        //->limit("100")
        ->get();
        $listingObjects = DB::table('objects')->select('ml_num')->get(); /* to do distinct */
        $listingIds = [];
        $existingIds = [];
        $existing_failed = DB::table('failed_sold_objects')->where('table',$tablename)->get()
                                ->pluck('ml_num')->toArray();
        
        foreach ($listingsdata as $item) {
            $listingIds[] = $item->ml_num;
        }

        foreach ($listingObjects as $item) {
            $existingIds[] = $item->ml_num;
        }
        $diff = array_diff($listingIds, $existingIds);
        $diff = array_diff($diff,$existing_failed);
       //dd(count($diff));
        $error_in_images_found = "";
        if (count($diff) > 0) {
            foreach (array_chunk($diff, 500) as $items) {
                foreach ($items as $item) {
                    $photos = $rets->GetObject("Property", "Photo", $item, '*', 0);
                    $photosData = $photos->toArray();
                    $imageID =1;
                    if (count($photosData) > 0) {
                        foreach ($photosData as $photo) {
                            
                            if (empty($photo->isError())) {
                                $imageContent = $photo->getContent();
                                $contentType = $photo->getContentType();
                                #$imageID = $photo->getObjectId();
                                if (!empty($contentType)) {
                                    $ext1 = explode('/', $contentType);
                                    $ext2 = explode(';', $ext1['1']);
                                    if ($ext2[0] == 'jpeg') {
                                        $ext = 'jpg';
                                    } else {
                                        $ext = $ext2[0];
                                    }
                                    $fileName = $item . '-' . $imageID . '.' . $ext;
                                    try {
                                        $image = $s3->putObject([
                                            'Bucket' => $Bucketname,
                                            'Key' => $fileName,
                                            'Body' => $imageContent,
                                            'ACL' => 'public-read',
                                        ]);
                                        $imgurl = "https://$Bucketname.s3.amazonaws.com/" . $fileName;
                                        DB::table('objects')->insert(['ml_num' => $item, 'url' => $imgurl]);
                                        $imageID++;
                                    } catch (Aws\S3\Exception\S3Exception $e) {
                                        echo "There was an error uploading the file. {$item}\n";
                                    }
                                }
                            } else {
                                echo "Error in downloading {$item}\n";
                                $error = $photo->getError()->getMessage();
                                DB::table('failed_sold_objects')
                                    ->updateOrInsert(
                                        ['ml_num' => $item],
                                        ['ml_num' => $item, 'error' => $error,"table"=>$tablename,"created_at"=>date("Y-m-d H:i:s"),"updated_at"=>date("Y-m-d H:i:s")]
                                );
                            }
                        }
                    }
                }
            }
        } else {
            echo "No images to download\n";
        }
        echo "downloadObjects $tablename done\n";
        \Log::info("Downloaded objects for $tablename count " . count($diff));
    }

    public static function downloadObjectsByMlNum($rets,$item)
    {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
        ]);
        $Bucketname = "dwellable-treb";
       
        $error_in_images_found = "";
        
        $photos = $rets->GetObject("Property", "Photo", $item, '*', 0);
        $photosData = $photos->toArray();
        
        $imageID = 1;   
        if (count($photosData) > 0) {
            foreach ($photosData as $photo) {
                if (empty($photo->isError())) {
                    $imageContent = $photo->getContent();
                    $contentType = $photo->getContentType();
                    #$imageID = $photo->getObjectId();
                    if (!empty($contentType)) {
                        $ext1 = explode('/', $contentType);
                        $ext2 = explode(';', $ext1['1']);
                        if ($ext2[0] == 'jpeg') {
                            $ext = 'jpg';
                        } else {
                            $ext = $ext2[0];
                        }
                        $fileName = $item . '-' . $imageID . '.' . $ext;
                        try {
                            $image = $s3->putObject([
                                'Bucket' => $Bucketname,
                                'Key' => $fileName,
                                'Body' => $imageContent,
                                'ACL' => 'public-read',
                            ]);
                            $imgurl = "https://$Bucketname.s3.amazonaws.com/" . $fileName;
                            DB::table('objects')->insert(['ml_num' => $item, 'url' => $imgurl]);
                            $imageID++;
                        } catch (Aws\S3\Exception\S3Exception $e) {
                            echo "There was an error uploading the file. {$item}\n";
                        }
                    }
                } else {
                    echo "Error in downloading {$item}\n";
                }
            }
        }
        
        
        echo "downloadObjects done\n";
        \Log::info("Downloaded objects for $item  ");
    }

    public static function downloadSoldObjects($table)
    {
        ini_set("memory_limit", "-1");
        set_time_limit(0);    
        
        $listingIds = DB::table($table)->select('ml_num')
        ->whereIn('area',['Toronto','Durham','York','Halton','Peel'])
        ->where(function($q){
           return $q->whereYear('cd', '>=', 2015)
                    ->orWhereYear('xd',">=",2015)
                    ->orWhereYear('dt_sus', '>=', 2015)
                    ->orWhereYear('dt_ter',">=",2015);
        })->pluck('ml_num')->toArray();
         
        $existingIds = DB::table('objects')->select('ml_num')
                            ->get()
                            ->pluck('ml_num')->toArray(); /* to do distinct */

        $existing_failed = DB::table('failed_sold_objects')->get()
                                ->pluck('ml_num')->toArray();
        
        $mlNum = array_diff($listingIds, $existingIds);
        $mlNum = array_diff($mlNum, $existing_failed);
        
        if (count($mlNum) > 0) {
            foreach ($mlNum as $ml_num) {
                self::getArchivePhoto($ml_num,$table);
            }
        }
        echo "downloadObjects done\n";
    }

    public static function getArchivePhoto($ml_num,$table){
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
        ]);
        $Bucketname = "dwellable-treb";
       
        $user_code = "AV19akr";
        $password = "36$28yz";
        
        for ($imageID=1; $imageID <=20 ; $imageID++) { 
            
            $curl = curl_init();
            $setup_array = array(
                CURLOPT_URL => "https://getfiles.torontomls.net/GetFiles/GetArchivePhoto.ashx?user_code=$user_code&password=$password&ml_num=$ml_num&img_num=$imageID",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "X_BLOCK_LINKS: NOTALINK"
                ),
            );
            
            curl_setopt_array($curl,$setup_array);
            $imageContent = curl_exec($curl);
            $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
            curl_close($curl);
            
            if(in_array($contentType,["image/jpg","image/jpeg"])){
                try {
                    $fileName = $ml_num . '-' . $imageID . '.' . 'jpg';
                    $image = $s3->putObject([
                        'Bucket' => $Bucketname,
                        'Key' => $fileName,
                        'Body' => $imageContent,
                        'ACL' => 'public-read',
                    ]);
                   $imgurl = "https://$Bucketname.s3.amazonaws.com/" . $fileName;
                   
                    DB::table('sold_objects')->insert(['ml_num' => $ml_num, 'url' => $imgurl]);
                } catch (Aws\S3\Exception\S3Exception $e) {
                    DB::table('failed_sold_objects')
                    ->updateOrInsert(
                        ['ml_num' => $ml_num],
                        ['ml_num' => $ml_num, 'error' => $imageContent,"table"=>$table,"created_at"=>date("Y-m-d H:i:s"),"updated_at"=>date("Y-m-d H:i:s")]
                    );
                }
            }else{
                if($imageContent=="ML number is NOT an archive listing"){
                   
                    DB::table('failed_sold_objects')
                    ->updateOrInsert(
                        ['ml_num' => $ml_num],
                        ['ml_num' => $ml_num, 'error' => $imageContent,"table"=>$table,"created_at"=>date("Y-m-d H:i:s"),"updated_at"=>date("Y-m-d H:i:s")]
                    );
                }
                
            }
        }
    }
}
