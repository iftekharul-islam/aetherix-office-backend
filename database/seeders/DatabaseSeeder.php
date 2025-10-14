<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use App\Models\SlackCredentials;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Division::create([
            'name' => 'Aetherix',
            'code' => 'AEX',
            'description' => 'Aetherix Division',
        ]);
        Division::create([
            'name' => 'Citizen',
            'code' => 'CTG',
            'description' => 'CityZen Division',
        ]);
        Department::create([
            'division_id' => 1,
            'name' => 'Software Development',
            'code' => 'SWD',
            'description' => 'Software Development Department',
        ]);

        Department::create([
            'division_id' => 2,
            'name' => 'Citizen Operations',
            'code' => 'CTO',
            'description' => 'CityZen Operations Department',
        ]);

        User::create([
            'name' => 'Admin',
            'machine_id' => 454545,
            'email' => 'admin@example.com',
            'role' => 'admin',
            'employee_id'=> 'Admin5729357239',
            'password' => Hash::make('123456'),
        ]);

       
    }
}
