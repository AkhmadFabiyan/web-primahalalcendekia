<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::subscribe(\App\Listeners\AuthEventSubscriber::class);
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\WorkflowACompleted::class,
            \App\Listeners\NotifyCompanionOnWorkflowACompleted::class,
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Workflows\Events\WorkflowStepCompleted::class,
            \App\Listeners\CheckWorkflowCompletionListener::class,
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Payments\Events\GovernmentInvoicePaid::class,
            \App\Listeners\CheckGovernmentInvoicePaidListener::class,
        );

        // Management Report Cache Observers
        \App\Modules\Leads\Models\Lead::observe(\App\Observers\ReportCacheObserver::class);
        \App\Modules\Projects\Models\Project::observe(\App\Observers\ReportCacheObserver::class);
        \App\Modules\Payments\Models\Invoice::observe(\App\Observers\ReportCacheObserver::class);
        \App\Modules\Payments\Models\Payment::observe(\App\Observers\ReportCacheObserver::class);
        \App\Modules\Projects\Models\Certificate::observe(\App\Observers\ReportCacheObserver::class);
        \App\Modules\Projects\Models\ProjectAssignment::observe(\App\Observers\ReportCacheObserver::class);
        \App\Modules\Workflows\Models\Task::observe(\App\Observers\ReportCacheObserver::class);
    }
}
