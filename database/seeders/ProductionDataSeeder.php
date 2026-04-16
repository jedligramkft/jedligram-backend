<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use \App\Models\Role;
use \App\Models\User;
use \App\Models\Thread;
use \App\Models\ThreadUser;

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

        $codedUsers = $this->getCodedUsersFromEnv();

        foreach ($codedUsers as $codedUser) {
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

        $profileImage = $this->resolveSeedAssetPath('header/ebedlo_profil.gif', 'images/ebedlo_profil.gif');
        $headerImage = $this->resolveSeedAssetPath('header/ebedlo_header.jpg', 'images/ebedlo_header.jpg');

        Thread::create([
            'name' => 'Ebédlő',
            'description' => 'Minden nap reggel hat órakkor az aznapi eatrendes kaja felkerül ide, és itt lehet megbeszélni, hogy mennyire jó vagy rossz az aznapi menü.',
            'rules' => 'A szabályok ugyanazok, mint a fórum többi részén, de kérjük, hogy csak az aznapi menüre vonatkozó hozzászólások legyenek itt.',
            'image' => $profileImage,
            'header' => $headerImage,
        ]);

        if (User::count() > 0 && Thread::count() > 0) {
            ThreadUser::create([
                'thread_id' => 1,
                'user_id' => 1,
                'role_id' => 1,
            ]);
        }
    }

    private function getCodedUsersFromEnv(): array
    {
        $codedUsersRaw = env('CODED_USERS', '');

        if (!is_string($codedUsersRaw) || trim($codedUsersRaw) === '') {
            return [];
        }

        $entries = array_filter(
            array_map('trim', explode(';', $codedUsersRaw)),
            static fn (string $entry): bool => $entry !== ''
        );

        $codedUsers = [];

        foreach ($entries as $entry) {
            $parts = explode(':', $entry, 2);

            if (count($parts) !== 2) {
                continue;
            }

            [$email, $password] = array_map('trim', $parts);

            if ($email === '' || $password === '') {
                continue;
            }

            $codedUsers[] = [
                'email' => $email,
                'password' => $password,
            ];
        }

        return $codedUsers;
    }

    private function resolveSeedAssetPath(string $storageRelativePath, string $publicRelativePath): string
    {
        if (file_exists(public_path($publicRelativePath))) {
            return $publicRelativePath;
        }

        if (file_exists(storage_path('app/public/' . $storageRelativePath))) {
            return $storageRelativePath;
        }

        return $publicRelativePath;
    }
}
