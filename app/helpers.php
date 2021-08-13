<?php
function toRetsTime($time)
{
    $date = date("Y-m-d", strtotime($time));
    $time = date("H:i:s", strtotime($time));
    $fDate = $date . 'T' . $time . "+";
    return $fDate;
}

function get_table_columns($table_name)
{
    $final_columns_array = array();
    $database = env('DB_DATABASE');
    $tbl_cols = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='$table_name' AND TABLE_SCHEMA='$database'");
    foreach ($tbl_cols as $column) {
        $final_columns_array[] = $column->COLUMN_NAME;
    }
    return $final_columns_array;
}
