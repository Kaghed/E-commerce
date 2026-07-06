<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
  
protected $fillable = [
        'order_id',
        'phone',
        'address',
        'image',
        'period',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
 
}
