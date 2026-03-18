<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Comment extends Model
{
    use HasRecursiveRelationships, HasFactory;

    protected $fillable = ['user_id', 'post_id', 'parent_id', 'content'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function post() {
        return $this->belongsTo(Post::class);
    }

    public function parent() {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function getParentKeyName()
    {
        return 'parent_id';
    }
}
