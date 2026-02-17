<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

/**
 * Class Post
 *
 * @property int $id
 * @property string $content
 * @property int $thread_id
 * @property int $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Thread $thread
 * @property User $user
 * @property Collection|Vote[] $votes
 *
 * @package App\Models
 */
class Post extends Model
{
    use HasFactory;
    protected $table = 'posts';

    protected $casts = [
        'thread_id' => 'int',
        'user_id' => 'int'
    ];

    protected $fillable = [
        'content',
        'thread_id',
        'user_id'
    ];

    protected $appends = ['score'];

    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function userVote()
    {
        return $this->hasOne(Vote::class)->where('user_id', Auth::id());
    }

    protected function score(): Attribute
    {
        return Attribute::make(
            get: function () {
                $up = $this->votes()->where('is_upvote', true)->count();
                $down = $this->votes()->where('is_upvote', false)->count();
                if ($up !== null && $down !== null) {
                    return $up - $down;
                }
                return 0;
            }
        );
    }
}
