<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentialProperties extends Model
{
    protected $table = 'residential_properties';
    protected $primaryKey = "ml_num";
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}
