<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VowResidentialProperty extends Model
{
    protected $primaryKey = "ml_num";
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}
