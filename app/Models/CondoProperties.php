<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CondoProperties extends Model
{
    protected $table = 'condo_properties';
    protected $primaryKey = "ml_num";
    public $incrementing = false;
    protected $guarded = [];
    public $timestamps = false;
}
