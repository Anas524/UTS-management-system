<?php

namespace App\Providers;

use App\Models\ExpenseRow;
use App\Models\ExpenseSheet;
use App\Policies\ExpenseRowPolicy;
use App\Policies\ExpenseSheetPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        ExpenseSheet::class => ExpenseSheetPolicy::class,
        ExpenseRow::class   => ExpenseRowPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
