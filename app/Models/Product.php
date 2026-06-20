<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'seller_id',
        'category_id',
        'title',
        'is_active',
        'governorate',
        'description',
        'price',
        'quantity',
        'product_image_url',
        'product_url'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function favoriters()
    {
        return $this->belongsToMany(User::class,'favorites','product_id','user_id')->withTimestamps();
    }
}
