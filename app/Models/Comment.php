<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Comment extends Model
{
    use HasRecursiveRelationships;

    protected $fillable = ['user_id', 'post_id', 'parent_id', 'content'];

    public function getParentKeyName()
    {
        return 'parent_id';
    }
}
