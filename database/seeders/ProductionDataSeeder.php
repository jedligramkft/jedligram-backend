<?php

namespace Database\Seeders;

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

        foreach ($this->getCodedUsersFromEnv() as $codedUser) {
            $email = $codedUser['email'] ?? null;
            $password = $codedUser['password'] ?? null;

            if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if (!is_string($password) || $password === '') {
                continue;
            }

            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => strstr($email, '@', true) ?: $email,
                    'email' => $email,
                    'password' => $password,
                    'display_email' => $email,
                ]
            );
        }

        Thread::create([
            'name' => 'Ebédlő',
            'description' => 'Minden nap reggel hat órakkor az aznapi eatrendes kaja felkerül ide, és itt lehet megbeszélni, hogy mennyire jó vagy rossz az aznapi menü.',
            'rules' => 'A szabályok ugyanazok, mint a fórum többi részén, de kérjük, hogy csak az aznapi menüre vonatkozó hozzászólások legyenek itt.',
            'image' => env('APP_URL').'/images/ebedlo_profil.gif',
            'header' => env('APP_URL').'/images/ebedlo_header.jpg',
        ]);

        ThreadUser::create([
            'thread_id' => 1,
            'user_id' => 1,
            'role_id' => 1,
        ]);
    }

    private function getCodedUsersFromEnv(): array
    {
        $codedUsersRaw = env('CODED_USERS', '[]');

        if (is_array($codedUsersRaw)) {
            return $codedUsersRaw;
        }

        if (!is_string($codedUsersRaw) || trim($codedUsersRaw) === '') {
            return [];
        }

        $decodedUsers = json_decode($codedUsersRaw, true);

        return is_array($decodedUsers) ? $decodedUsers : [];
    }
}
