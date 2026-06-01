<?php

namespace Database\Seeders;

use App\Models\Profile;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        //** admin */

        $user = User::create([
            'username' => "test1",
            'email' => 'testEmail1@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'admin'
        ]);

        Profile::create([
            'user_id' => $user->id,
            'full_name' => 'Ali Ali',
            'date_of_birth' => '1990-05-15',
            'profile_image_url' => 'images/profiles/profile1.jpg',
        ]);
        $token1 = $user->createToken('authToken')->plainTextToken;

        //** seller */

        $user = User::create([
            'username' => "test2",
            'email' => 'testEmail2@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'seller'
        ]);

        Profile::create([
            'user_id' => $user->id,
            'full_name' => 'Aghed Aghed',
            'date_of_birth' => '1998-07-05',
            'profile_image_url' => 'images/profiles/profile2.jpg',
        ]);
        $token2 = $user->createToken('authToken')->plainTextToken;

        //** customer */
        $user = User::create([
            'username' => "test3",
            'email' => 'testEmail3@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'customer'
        ]);

        Profile::create([
            'user_id' => $user->id,
            'full_name' => 'Hasan Hasan',
            'date_of_birth' => '1991-11-21',
            'profile_image_url' => 'images/profiles/profile3.jpg',
        ]);
        $token3 = $user->createToken('authToken')->plainTextToken;

        echo "admin: " . $token1 . PHP_EOL;
        echo "seller: " . $token2 . PHP_EOL;
        echo "customer: " . $token3 . PHP_EOL;

    }
}
