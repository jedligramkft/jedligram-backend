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
use Illuminate\Database\Eloquent\Builder;

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
        'user_id',
        'image'
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

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function upvotes()
    {
        return $this->votes()->where('is_upvote', true);
    }

    public function downvotes()
    {
        return $this->votes()->where('is_upvote', false);
    }

    public function myVote()
    {
        return $this->hasOne(Vote::class)->where('user_id', Auth::id());
    }

    public function scopeWithMyVote(Builder $query): Builder
    {
        if (!auth()->check()) {
            return $query;
        }

        return $query->with([
            'myVote' => fn($q) => $q
                ->select(['id', 'post_id', 'is_upvote']),
        ]);
    }

    protected function score(): Attribute
    {
        return Attribute::make(
            get: function () {

                if (array_key_exists('upvotes_count', $this->attributes) && array_key_exists('downvotes_count', $this->attributes)) {
                    return $this->attributes['upvotes_count'] - $this->attributes['downvotes_count'];
                }
                return $this->upvotes()->count() - $this->downvotes()->count();
            }
        );
    }
}
