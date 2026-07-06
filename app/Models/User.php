<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens;
    protected $guarded = ['id'];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function wallet(){
        return $this->hasOne(Wallet::class);
    }

    public function favoriteProducts(){
        return $this->belongsToMany(Product::class,'favorites','user_id','product_id')->withTimestamps();
    }

     public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }
   public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function advertisements()
    {
        return $this->hasMany(Advertisment::class);
    }

    public function supportRequests(){
        return $this->hasMany(SupportRequest::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

}
