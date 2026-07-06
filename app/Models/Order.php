<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'product_id',
        'transaction_id',
        'quantity',
        'total_price',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function shipping()
    {
        return $this->hasOne(Shipping::class, 'order_id');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }
   
}
