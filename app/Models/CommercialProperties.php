<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialProperties extends Model
{
    protected $table = 'commercial_properties';
    protected $primaryKey = "ml_num";
    public $incrementing = false;
    protected $guarded = [];
    public $timestamps = false;

}
