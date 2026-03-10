<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadUser;
use App\Models\User;
use App\Models\Vote;
use App\Models\Comment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::factory(20)->create();

        Thread::factory(3)->create()
            ->each(function ($thread) use ($users) {
                $users->random(rand(3, 10))->each(function ($user) use ($thread) {
                    ThreadUser::create([
                        'thread_id' => $thread->id,
                        'user_id' => $user->id,
                        'role_id' => 3, //user role
                    ]);
                });

                $posts = Post::factory(2)->create([
                    'thread_id' => $thread->id,
                    'user_id' => $users->random()->id,
                ])
                    ->each(function ($post) use ($users) {
                        $users->random(rand(0, 10))->each(function ($user) use ($post) {
                            Vote::factory()->create([
                                'post_id' => $post->id,
                                'user_id' => $user->id,
                            ]);
                        });
                    });
                $comments = collect();
                foreach ($posts as $post) {
                    $created = Comment::factory(rand(0, 5))->for($post)->for($users->random())->create();
                    if ($created instanceof \Illuminate\Database\Eloquent\Collection) {
                        $comments = $comments->concat($created);
                    } elseif ($created) {
                        $comments->push($created);
                    }
                }

                $comments->each(function ($comment) {
                    if (rand(1, 100) <= 50) {
                        $replies = Comment::factory(rand(1, 2))->reply($comment)->create();
                        if (rand(1, 100) <= 20) {
                            Comment::factory(1)->reply($replies->first())->create();
                        }
                    }
                });
            });
    }
}
