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

        Thread::create([
            'name' => 'Ebédlő',
            'description' => 'Minden nap reggel hat órakkor az aznapi eatrendes kaja felkerül ide, és itt lehet megbeszélni, hogy mennyire jó vagy rossz az aznapi menü.',
            'rules' => 'A szabályok ugyanazok, mint a fórum többi részén, de kérjük, hogy csak az aznapi menüre vonatkozó hozzászólások legyenek itt.',
            'image' => 'images/ebedlo_profil.gif',
            'header' => 'images/ebedlo_header.jpg',
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
        $codedUsersRaw = env('CODED_USERS', '[]');

        if (is_array($codedUsersRaw)) {
            return $codedUsersRaw;
        }

        if (!is_string($codedUsersRaw) || trim($codedUsersRaw) === '') {
            return [];
        }

        $decodedUsers = json_decode($codedUsersRaw, true);

        if (is_array($decodedUsers)) {
            return $decodedUsers;
        }

        $normalized = $this->normalizeJsonLikeUsers($codedUsersRaw);

        if ($normalized === null) {
            return [];
        }

        $decodedUsers = json_decode($normalized, true);

        return is_array($decodedUsers) ? $decodedUsers : [];
    }

    private function normalizeJsonLikeUsers(string $codedUsersRaw): ?string
    {
        if (!str_contains($codedUsersRaw, "'")) {
            return null;
        }

        $normalized = preg_replace_callback(
            "/'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'/",
            static function (array $matches): string {
                $value = str_replace("\\'", "'", $matches[1]);

                return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '""';
            },
            $codedUsersRaw
        );

        if (!is_string($normalized)) {
            return null;
        }

        // Allow trailing commas in manually written secret values.
        $normalized = preg_replace('/,\s*([\]}])/m', '$1', $normalized);

        return is_string($normalized) ? $normalized : null;
    }
}
