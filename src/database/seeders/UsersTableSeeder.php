<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => '偽山田　贋作',
            'email' => 'dummy@test.com',
            'email_verified_at' => '2026-01-01 00:00:01',
            'password' => Hash::make('dummypass')
        ];

        DB::table('users')->insert($param);
    }
}
