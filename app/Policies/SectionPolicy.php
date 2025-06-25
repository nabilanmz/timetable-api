<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SectionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Section  $section
     * @return bool
     */
    public function update(User $user, Section $section)
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Section  $section
     * @return bool
     */
    public function delete(User $user, Section $section)
    {
        return $user->is_admin;
    }
}
