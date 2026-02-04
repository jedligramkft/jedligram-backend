<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ThreadUser
 * 
 * @property int $id
 * @property int $thread_id
 * @property int $user_id
 * @property int $role_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Role $role
 * @property Thread $thread
 * @property User $user
 *
 * @package App\Models
 */
class ThreadUser extends Model
{
	protected $table = 'thread_user';

	protected $casts = [
		'thread_id' => 'int',
		'user_id' => 'int',
		'role_id' => 'int'
	];

	protected $fillable = [
		'thread_id',
		'user_id',
		'role_id'
	];

	public function role()
	{
		return $this->belongsTo(Role::class);
	}

	public function thread()
	{
		return $this->belongsTo(Thread::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
