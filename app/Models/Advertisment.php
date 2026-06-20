<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advertisment extends Model
{
    protected $guarded = ['id'];

    public function transaction(){
        return $this->hasOne(Transaction::class);
    }
}
