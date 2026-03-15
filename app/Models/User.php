<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
class User extends Authenticatable
{
    use HasApiTokens, HasFactory;
	protected $table = 'users';

	protected $casts = [
		// 'email_verified_at' => 'datetime',
		'welcome_email_sent_at' => 'datetime',
        'password' => 'hashed'
	];

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $fillable = [
		'name',
		'email',
		// 'email_verified_at',
		'welcome_email_sent_at',
		'password',
		'remember_token'
	];

	public function posts()
	{
		return $this->hasMany(Post::class);
	}

	public function threads()
	{
		return $this->belongsToMany(Thread::class)
					->withPivot('id', 'role_id')
					->withTimestamps();
	}

	public function votes()
	{
		return $this->hasMany(Vote::class);
	}
}
