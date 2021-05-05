<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterFaskesType extends Model
{
    protected $table = 'master_faskes_types';
    protected $fillable = ['name', 'is_imported', 'non_public'];

    public function masterFaskes()
    {
        return $this->belongsToOne('App\MasterFaskes', 'id_tipe_faskes');
    }

    public function agency()
    {
        return $this->hasMany('App\Agency', 'agency_type');
    }
}
