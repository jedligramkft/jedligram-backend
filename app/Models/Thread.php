<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

/**
 * Class Thread
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Post[] $posts
 * @property Collection|User[] $users
 *
 * @package App\Models
 */
class Thread extends Model
{
    use HasFactory;
    use Searchable;
    protected $table = 'threads';

    protected $fillable = [
        'name',
        'description',
        'rules',
        'image',
        'header'
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function membership()
    {
        return $this->hasOne(ThreadUser::class);
    }

    public function scopeWithMembershipForUser(Builder $query, ?int $userId = null): Builder
    {
        $userId ??= auth()->id();

        if (! $userId) {
            return $query;
        }

        return $query->with([
            'membership' => fn ($q) => $q
                ->where('user_id', $userId)
                ->select(['id', 'thread_id', 'user_id', 'role_id'])
                ->with('role:id,name'),
        ]);
    }

    public function toSearchableArray()
    {
        return [
            // 'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'thread_user')
            ->using(ThreadUser::class)
            ->withPivot('id', 'role_id')
            ->withTimestamps();
    }

    public function isMember(User $user)
    {
        return $this->users()->whereKey($user->id)->exists();
    }
}
