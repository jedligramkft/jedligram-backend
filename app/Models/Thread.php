<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
	protected $table = 'threads';

	protected $fillable = [
		'name',
		'description'
	];

	public function posts()
	{
		return $this->hasMany(Post::class);
	}

	public function users()
	{
		return $this->belongsToMany(User::class)
					->withPivot('id', 'role_id')
					->withTimestamps();
	}
}
