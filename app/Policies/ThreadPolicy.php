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
        return (!$thread->isMember($user)) || $user->hasThreadRole($thread->id, [1, 2, 3]);
    }

    public function viewMembers(User $user, Thread $thread): bool
    {
        return $thread->isMember($user);
    }

    public function viewPosts(User $user, Thread $thread): bool
    {
        return ! $user->hasThreadRole($thread->id, [4]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function upload(User $user, Thread $thread): bool
    {
        return $user->hasThreadRole($thread->id, [1]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function updateRole(User $user, Thread $thread): bool
    {
        return $user->hasThreadRole($thread->id, [1]);
    }

    public function ban(User $user, Thread $thread, User $model): Response
    {
        if ($user->id === $model->id) {
            return Response::deny('You cannot ban yourself.');
        }

        if (! $user->hasThreadRole($thread->id, [1, 2])) {
            return Response::deny('You must be an admin or moderator in this thread.');
        }

        return Response::allow();
    }

    public function update(User $user, Thread $thread)
    {
        return $user->hasThreadRole($thread->id, [1]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Thread $thread): bool
    {
        return $user->hasThreadRole($thread->id, [1, 2, 3]);
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
