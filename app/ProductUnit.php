<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    protected $table = 'product_unit';
    protected $fillable = ['product_id', 'unit_id'];
}
