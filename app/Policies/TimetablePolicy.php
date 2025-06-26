<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Timetable;
use Illuminate\Auth\Access\HandlesAuthorization;

class TimetablePolicy
{
    use HandlesAuthorization;

    public function manageSections(User $user, Timetable $timetable)
    {
        return $user->is_admin;
    }
}
