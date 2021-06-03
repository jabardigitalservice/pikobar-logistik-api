<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterUnit extends Model
{
    protected $table = 'master_unit';
    protected $fillable = ['unit', 'is_imported'];
}
