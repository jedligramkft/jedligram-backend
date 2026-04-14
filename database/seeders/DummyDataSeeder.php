<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Role;
use App\Models\Thread;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    private const USERS_TO_CREATE = 20;
    private const THREADS_TO_CREATE = 1;
    private const POSTS_PER_THREAD_MIN = 1;
    private const POSTS_PER_THREAD_MAX = 2;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleIds = $this->seedRoles();
        $users = $this->seedUsers();

        for ($i = 0; $i < self::THREADS_TO_CREATE; $i++) {
            $thread = Thread::create([
                'name' => $this->uniqueThreadName(),
                'description' => Str::limit(fake()->sentence(10), 50, ''),
                'rules' => implode("\n", fake()->sentences(4)),
            ]);

            $activeMembers = $this->seedThreadMembers($thread, $users, $roleIds);
            $posts = $this->seedPostsForThread($thread, $activeMembers);

            foreach ($posts as $post) {
                $this->seedCommentsForPost($post, $activeMembers);
                $this->seedVotesForPost($post, $activeMembers);
            }
        }

        $this->seedThreadWithUserOneAsAdmin($users, $roleIds);
        $this->seedThreadWithUserOneAsModerator($users, $roleIds);
    }

    private function seedThreadWithUserOneAsAdmin(Collection $users, array $roleIds): void
    {
        $admin = $users->firstWhere('id', 1);

        if (! $admin instanceof User) {
            return;
        }

        $thread = Thread::create([
            'name' => $this->uniqueThreadName(),
            'description' => Str::limit(fake()->sentence(10), 50, ''),
            'rules' => implode("\n", fake()->sentences(4)),
        ]);

        $activeMembers = $this->seedThreadMembers($thread, $users, $roleIds, $admin, 4, 7);
        $posts = $this->seedPostsForThread($thread, $activeMembers);

        foreach ($posts as $post) {
            $this->seedCommentsForPost($post, $activeMembers);
            $this->seedVotesForPost($post, $activeMembers);
        }
    }

    private function seedThreadWithUserOneAsModerator(Collection $users, array $roleIds): void
    {
        $moderator = $users->firstWhere('id', 1);

        if (! $moderator instanceof User) {
            return;
        }

        $admin = $users->where('id', '!=', $moderator->id)->shuffle()->first();

        if (! $admin instanceof User) {
            return;
        }

        $thread = Thread::create([
            'name' => $this->uniqueThreadName(),
            'description' => Str::limit(fake()->sentence(10), 50, ''),
            'rules' => implode("\n", fake()->sentences(4)),
        ]);

        $this->seedThreadMembers($thread, $users, $roleIds, $admin, 4, 7);

        $thread->users()->syncWithoutDetaching([
            $moderator->id => ['role_id' => $roleIds['moderator']],
        ]);

        $activeRoleIds = [$roleIds['admin'], $roleIds['moderator'], $roleIds['user']];
        $activeMembers = $thread->users()
            ->wherePivotIn('role_id', $activeRoleIds)
            ->get();

        $posts = $this->seedPostsForThread($thread, $activeMembers);

        foreach ($posts as $post) {
            $this->seedCommentsForPost($post, $activeMembers);
            $this->seedVotesForPost($post, $activeMembers);
        }
    }

    /**
     * Ensure thread-role records exist and return IDs keyed by name.
     *
     * @return array{admin:int,moderator:int,user:int,banned:int}
     */
    private function seedRoles(): array
    {
        foreach (['admin', 'moderator', 'user', 'banned'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        /** @var array<string,int> $roles */
        $roles = Role::query()
            ->whereIn('name', ['admin', 'moderator', 'user', 'banned'])
            ->pluck('id', 'name')
            ->map(fn ($id) => (int) $id)
            ->all();

        return [
            'admin' => $roles['admin'],
            'moderator' => $roles['moderator'],
            'user' => $roles['user'],
            'banned' => $roles['banned'],
        ];
    }

    private function seedUsers(): Collection
    {
        $existingUsers = User::query()->get();
        $newUsers = User::factory(self::USERS_TO_CREATE)->create();

        $newUsers->each(function (User $user): void {
            $user->update([
                'display_name' => $user->name,
                'display_email' => $user->email,
                'bio' => fake()->boolean(80) ? Str::limit(fake()->sentence(12), 100, '') : null,
                'is_2fa_enabled' => fake()->boolean(15),
            ]);
        });

        return $existingUsers->concat($newUsers)->values();
    }

    /**
     * Attach users to a thread with mixed roles and return active members.
     */
    private function seedThreadMembers(
        Thread $thread,
        Collection $users,
        array $roleIds,
        ?User $forcedAdmin = null,
        int $minMembers = 8,
        int $maxMembers = 14,
    ): Collection
    {
        $memberCount = min($users->count(), random_int($minMembers, $maxMembers));

        if ($forcedAdmin instanceof User) {
            $members = $users
                ->where('id', '!=', $forcedAdmin->id)
                ->shuffle()
                ->take(max($memberCount - 1, 0))
                ->prepend($forcedAdmin)
                ->values();
        } else {
            $members = $users->shuffle()->take($memberCount)->values();
        }

        /** @var array<int,int> $memberRoles */
        $memberRoles = [];

        $admin = $members->first();
        $memberRoles[$admin->id] = $roleIds['admin'];

        $remaining = $members->slice(1)->values();
        $moderatorCount = min($remaining->count(), random_int(1, 2));
        $moderators = $remaining->take($moderatorCount);

        foreach ($moderators as $moderator) {
            $memberRoles[$moderator->id] = $roleIds['moderator'];
        }

        $normalMembers = $remaining->slice($moderatorCount)->values();
        foreach ($normalMembers as $member) {
            $memberRoles[$member->id] = $roleIds['user'];
        }

        $banCount = min($normalMembers->count(), random_int(0, 2));
        $bannedUserIds = $normalMembers->shuffle()->take($banCount)->pluck('id');
        foreach ($bannedUserIds as $userId) {
            $memberRoles[(int) $userId] = $roleIds['banned'];
        }

        $pivotPayload = [];
        foreach ($memberRoles as $userId => $roleId) {
            $pivotPayload[$userId] = ['role_id' => $roleId];
        }

        $thread->users()->syncWithoutDetaching($pivotPayload);

        $activeRoleIds = [$roleIds['admin'], $roleIds['moderator'], $roleIds['user']];

        return $thread->users()
            ->wherePivotIn('role_id', $activeRoleIds)
            ->get();
    }

    private function seedPostsForThread(Thread $thread, Collection $members): Collection
    {
        $posts = new Collection();
        $postCount = random_int(self::POSTS_PER_THREAD_MIN, self::POSTS_PER_THREAD_MAX);

        for ($i = 0; $i < $postCount; $i++) {
            $author = $members->random();

            $post = Post::create([
                'thread_id' => $thread->id,
                'user_id' => $author->id,
                'content' => fake()->paragraphs(random_int(1, 3), true),
                'image' => random_int(1, 100) <= 18
                    ? 'threads/' . $thread->id . '/posts/dummy-' . Str::uuid() . '.jpg'
                    : null,
            ]);

            $createdAt = now()->subMinutes(random_int(10, 60 * 24 * 21));
            $post->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();

            $posts->push($post);
        }

        return $posts;
    }

    private function seedCommentsForPost(Post $post, Collection $members): void
    {
        $topLevelCount = random_int(0, 2);
        $topLevelComments = new Collection();

        for ($i = 0; $i < $topLevelCount; $i++) {
            $topLevelComments->push($this->createComment($post, $members->random()));
        }

        foreach ($topLevelComments as $topLevelComment) {
            $replyCount = random_int(0, 2);
            $firstLevelReplies = new Collection();

            for ($i = 0; $i < $replyCount; $i++) {
                $firstLevelReplies->push(
                    $this->createComment($post, $members->random(), $topLevelComment->id)
                );
            }

            foreach ($firstLevelReplies as $firstLevelReply) {
                $secondLevelCount = random_int(0, 2);
                for ($i = 0; $i < $secondLevelCount; $i++) {
                    $this->createComment($post, $members->random(), $firstLevelReply->id);
                }
            }
        }
    }

    private function createComment(Post $post, User $author, ?int $parentId = null): Comment
    {
        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'parent_id' => $parentId,
            'content' => fake()->sentence(random_int(8, 18)),
        ]);

        $createdAt = now()->subMinutes(random_int(5, 60 * 24 * 14));
        $comment->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $comment;
    }

    private function seedVotesForPost(Post $post, Collection $members): void
    {
        $voterPool = $members
            ->where('id', '!=', $post->user_id)
            ->values();

        if ($voterPool->isEmpty()) {
            $voterPool = $members;
        }

        $maxVotes = min($voterPool->count(), random_int(0, 12));
        $voters = $voterPool->shuffle()->take($maxVotes);

        foreach ($voters as $voter) {
            $vote = Vote::create([
                'post_id' => $post->id,
                'user_id' => $voter->id,
                'is_upvote' => random_int(1, 100) <= 70,
            ]);

            $createdAt = now()->subMinutes(random_int(1, 60 * 24 * 10));
            $vote->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();
        }
    }

    private function uniqueThreadName(): string
    {
        do {
            $name = 'thread-' . Str::lower(Str::random(8));
        } while (Thread::query()->where('name', $name)->exists());

        return $name;
    }
}
