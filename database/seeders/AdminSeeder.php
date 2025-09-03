<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admins')->insert([
            'name' => 'Super Admin',
            'email' => 'superAdmin@mail.com',
            'password' => Hash::make('123456'),
            'phoNum'=>'0111223344',
            'address' => 'Moharram Bey',
            'role_id' => '1',
            'status' => 'active',

        ]);
        DB::table('admins')->insert([
            'name' => 'Admin',
            'email' => 'admin@mail.com',
            'password' => Hash::make('234567'),
            'phoNum'=>'0111334455',
            'address' => 'Moharram Bey',
            'role_id' => '2',
            'status' => 'active',

        ]);
        DB::table('admins')->insert([
            'name' => 'Employee',
            'email' => 'employee@mail.com',
            'password' => Hash::make('345678'),
            'phoNum'=>'0111445566',
            'address' => 'Moharram Bey',
            'role_id' => '3',
            'status' => 'active',

        ]);
    }
}
