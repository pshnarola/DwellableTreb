<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeleteTriggerForCondoProperties extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("CREATE TRIGGER `before_delete_condo_rows` BEFORE DELETE ON `vow_condo_properties` FOR EACH ROW 
                INSERT INTO `property_history` (`addr`, `municipality`, `lp_dol`, `sp_dol`,`status`,`ml_num`,`county`,`zip`, `property_type`,`lsc`,`created_at`,`updated_at`) 
                VALUES ( OLD.addr, OLD.municipality, OLD.lp_dol,OLD.lp_dol, OLD.status, OLD.ml_num, OLD.county, OLD.zip, 'residential',OLD.lsc,OLD.timestamp_sql,OLD.timestamp_sql);");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('DROP TRIGGER before_delete_condo_rows');
    }
}
