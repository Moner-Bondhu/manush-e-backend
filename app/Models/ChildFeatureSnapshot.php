<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ChildFeatureSnapshot extends Model { protected $fillable=['profile_id','domain','feature','value','source_type','source_id','measured_at']; protected $casts=['value'=>'float','measured_at'=>'datetime']; }
