<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => '管理者',
            'email' => 'admin@test.com',
            'email_verified_at' => '2026-01-01 00:00:01',
            'password' => Hash::make('adminpass')
        ];

        DB::table('admins')->insert($param);
    }
}
