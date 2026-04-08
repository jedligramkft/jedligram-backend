<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\Role;
use \App\Models\User;

class ProductionDataSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin',
            'moderator',
            'user',
            'banned'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $adminUser = [
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => 'adminadmin',
            'display_email' => 'admin@admin.com'
        ];

        User::firstOrCreate($adminUser);
    }
}
