<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = ['id'];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function advertisment(){
        return $this->hasOne(Advertisment::class);
    }
}
