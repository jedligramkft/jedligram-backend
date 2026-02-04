<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Vote
 * 
 * @property int $id
 * @property int $post_id
 * @property int $user_id
 * @property bool $is_upvote
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Post $post
 * @property User $user
 *
 * @package App\Models
 */
class Vote extends Model
{
	protected $table = 'votes';

	protected $casts = [
		'post_id' => 'int',
		'user_id' => 'int',
		'is_upvote' => 'bool'
	];

	protected $fillable = [
		'post_id',
		'user_id',
		'is_upvote'
	];

	public function post()
	{
		return $this->belongsTo(Post::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
