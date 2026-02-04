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

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ProductionDataSeeder::class);

        $users = User::factory(50)->create();

        Thread::factory(10)->create()
        ->each(function ($thread) use ($users) {
            $users->random(rand(3, 10))->each(function ($user) use ($thread) {
                ThreadUser::create([
                    'thread_id' => $thread->id,
                    'user_id' => $user->id,
                    'role_id' => 3, //user role
                ]);
            });

            $posts = Post::factory(10)->create([
                'thread_id' => $thread->id,
                'user_id' => $users->random()->id,
            ])
            ->each(function($post) use ($users) {
                $users->random(rand(0, 10))->each(function($user) use ($post){
                   Vote::factory()->create([
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                    ]); 
                });
            });
        });

    }
}
