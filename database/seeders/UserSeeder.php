<?php

namespace Database\Seeders;

use App\Models\DeviceToken;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        //** Admin for Aghed */

        $user = User::create([
            'email' => 'testEmail1@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'admin'
        ]);

        Profile::create([
            'user_id' => $user->id,
            'first_name' =>'Aghed',
            'last_name' =>'Alkhateb',
            'date_of_birth' => '1990-05-15',
            'governorate'=>'Damascus',
            'profile_image_url' => 'images/profiles/profile1.jpg',
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'balance' => 120.0,
            'wallet_pin' => Hash::make(1234)
        ]);

        DeviceToken::Create([
               'user_id' => $user->id,
               'token' => 'cSIjUVJdZGkWpoHTZIrgri:APA91bGqNGDfo0TVCOVGM4oWKpTWWSAawft9UDPjBT7fYdn0c__P-xpsuaYWirZ-2_G6G0CHM4-KdJAWh_efpDqRJ4PotKaRJff_Z85_lLd_fPcb0MubSzE'
                ]);
        $token1 = $user->createToken('authToken')->plainTextToken;

            //** Admin for Hasan */


        $user = User::create([
            'email' => 'testEmail11@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'admin'
        ]);

        Profile::create([
            'user_id' => $user->id,
            'first_name' => 'Hasan',
            'last_name' => 'Alkrad',
            'date_of_birth' => '1990-05-15',
            'governorate' => 'Daraa',
            'profile_image_url' => 'images/profiles/profile1.jpg',
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'balance' => 120.0,
            'wallet_pin' => Hash::make(1234)
        ]);

        // DeviceToken::Create([
        //     'user_id' => $user->id,
        //     'token' => 'f4lH24G78vO6keX8Ksxt6e:APA91bEhhLEff2jxjQ3dVC0sEDQsqUUgHh6q1KLKLG1RsQ5_aeoREu6qwBG-DZj6QjhtNyFCpF0VXPuf7ib-uuro6_EIdI9tR5vR4hKfiWFJRAUv5E0vVFo'
        // ]);
        $token11 = $user->createToken('authToken')->plainTextToken;


        //** seller */

        $user = User::create([
            'email' => 'testEmail2@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'seller'
        ]);

        Profile::create([
            'user_id' => $user->id,
            'first_name'=>'Ali' ,
            'last_name' =>'Mohammed',
            'date_of_birth' => '1998-07-05',
            'governorate' => 'Latakia',
            'profile_image_url' => 'profiles/profile2.jpg',
            'identity_image_url' => 'identities/identity2.jpg'
        ]);

        Wallet::create([
            'user_id'=>$user->id,
            'balance'=>120.0,
            'wallet_pin'=>Hash::make(1234)
        ]);

       
        $token2 = $user->createToken('authToken')->plainTextToken;

        //** customer */
        $user = User::create([
            'email' => 'testEmail3@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'customer'
        ]);

        Profile::create([
            'user_id' => $user->id,
            'first_name' => 'Abdallah',
            'last_name'=>'Atmah',
            'governorate' => 'Daraa',
            'date_of_birth' => '1991-11-21',
            'profile_image_url' => 'images/profiles/profile3.jpg',
        ]);

        Wallet::create([
            'user_id'=>$user->id,
            'balance' => 12000.0,
            'wallet_pin' => Hash::make(1234)
        ]);

         DeviceToken::Create([
            'user_id' => $user->id,
            'token' => 'f4lH24G78vO6keX8Ksxt6e:APA91bEhhLEff2jxjQ3dVC0sEDQsqUUgHh6q1KLKLG1RsQ5_aeoREu6qwBG-DZj6QjhtNyFCpF0VXPuf7ib-uuro6_EIdI9tR5vR4hKfiWFJRAUv5E0vVFo'
        ]);

        $token3 = $user->createToken('authToken')->plainTextToken;

        echo "admin aghed : " . $token1 . PHP_EOL;
        echo "admin hasan : " . $token11 . PHP_EOL;
        echo "seller: " . $token2 . PHP_EOL;
        echo "customer: " . $token3 . PHP_EOL;

    }
}
