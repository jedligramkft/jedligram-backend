<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use LdapRecord\Laravel\Auth\AuthenticatesWithLdap;
use LdapRecord\Laravel\Auth\LdapAuthenticatable;

/**
 * Class User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $welcome_email_sent_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Post[] $posts
 * @property Collection|Thread[] $threads
 * @property Collection|Vote[] $votes
 *
 * @package App\Models
 */
class User extends Authenticatable implements LdapAuthenticatable
{
    use HasApiTokens, HasFactory, Searchable, AuthenticatesWithLdap;
	protected $table = 'users';

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }

	protected $casts = [
		'email_verified_at' => 'datetime',
		'welcome_email_sent_at' => 'datetime',
		'is_2fa_enabled' => 'boolean',
        'password' => 'hashed'
	];

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $fillable = [
		'name',
		'email',
		'email_verified_at',
		'welcome_email_sent_at',
		'is_2fa_enabled',
		'password',
		'remember_token',
        'image',
        'bio',
        'display_name',
        'display_email'
	];

	public function posts()
	{
		return $this->hasMany(Post::class);
	}

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

	public function threads()
	{
		return $this->belongsToMany(Thread::class, 'thread_user')
                    ->using(ThreadUser::class)
					->withPivot('id', 'role_id')
					->withTimestamps();
	}

    protected array $threadRolesCache = [];

    public function hasThreadRole(int $threadId, array $roleIds): bool{
        if(!isset($this->threadRolesCache[$threadId])){
            $thread = $this->threads()
                ->where('thread_id', $threadId)
                ->first();

            $this->threadRolesCache[$threadId] = $thread && $thread->pivot ? $thread->pivot->role_id : null;
        }

        return in_array($this->threadRolesCache[$threadId], $roleIds);
    }

	public function votes()
	{
		return $this->hasMany(Vote::class);
	}

	public function postVotes(): HasManyThrough
	{
		return $this->hasManyThrough(
			Vote::class,
			Post::class,
			'user_id',
			'post_id',
			'id',
			'id'
		);
	}

	public function receivedUpvotes(): HasManyThrough
	{
		return $this->postVotes()->where('is_upvote', true);
	}

	public function receivedDownvotes(): HasManyThrough
	{
		return $this->postVotes()->where('is_upvote', false);
	}

	public function scopeWithPostKarmaCounts(Builder $query): Builder
	{
		return $query->withCount([
			'receivedUpvotes as received_upvotes_count',
			'receivedDownvotes as received_downvotes_count',
		]);
	}

	protected function postKarma(): Attribute
	{
		return Attribute::make(
			get: function (): int {
				if (
					array_key_exists('received_upvotes_count', $this->attributes)
					&& array_key_exists('received_downvotes_count', $this->attributes)
				) {
					return (int) $this->attributes['received_upvotes_count']
						- (int) $this->attributes['received_downvotes_count'];
				}

				return $this->receivedUpvotes()->count() - $this->receivedDownvotes()->count();
			}
		);
	}
}
