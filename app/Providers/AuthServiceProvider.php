<?php

namespace App\Providers;

use App\Models\Section;
use App\Models\Timetable;
use App\Policies\SectionPolicy;
use App\Policies\TimetablePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Section::class => SectionPolicy::class,
        Timetable::class => TimetablePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
