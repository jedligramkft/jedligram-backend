<?php

namespace App\Policies;

use App\Models\Thread;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ThreadPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Thread $thread): bool
    {
        return false;
    }

    public function viewMembers(User $user, Thread $thread): bool
    {
        // return true;
        return $user->hasThreadRole($thread->id, [1, 2]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function updateRole(User $user, Thread $thread): bool
    {
        return $user->hasThreadRole($thread->id, [1]);
    }

    public function ban(User $user, Thread $thread, User $model): bool
    {
        return $user->hasThreadRole($thread->id, [1, 2]) && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Thread $thread): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Thread $thread): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Thread $thread): bool
    {
        return false;
    }

    public function userCheck(User $user, Thread $thread): bool
    {
        return $thread->users->contains($user->id);
    }
}
