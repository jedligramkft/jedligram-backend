<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Thread;
use App\Models\Post;
use App\Models\ThreadUser;
use App\Models\Vote;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\ProductionDataSeeder;
use App\Models\Comment;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ProductionDataSeeder::class);

        $this->call(DummyDataSeeder::class);
    }
}
