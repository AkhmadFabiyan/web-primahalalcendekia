<?php

namespace App\Providers;

use App\Events\WorkflowACompleted;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Listeners\AuthEventSubscriber;
use App\Listeners\CheckGovernmentInvoicePaidListener;
use App\Listeners\CheckWorkflowCompletionListener;
use App\Listeners\NotifyCompanionOnWorkflowACompleted;
use App\Modules\Leads\Models\Lead;
use App\Modules\Payments\Events\GovernmentInvoicePaid;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Models\Payment;
use App\Modules\Projects\Models\Certificate;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectAssignment;
use App\Modules\Workflows\Events\WorkflowStepCompleted;
use App\Modules\Workflows\Models\Task;
use App\Observers\ReportCacheObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        Livewire::component(
            'filament.admin.resources.clients.pages.list-clients',
            ListClients::class,
        );

        RateLimiter::for('api-read', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api-write', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api-auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
        Event::subscribe(AuthEventSubscriber::class);
        Event::listen(
            WorkflowACompleted::class,
            NotifyCompanionOnWorkflowACompleted::class,
        );
        Event::listen(
            WorkflowStepCompleted::class,
            CheckWorkflowCompletionListener::class,
        );
        Event::listen(
            GovernmentInvoicePaid::class,
            CheckGovernmentInvoicePaidListener::class,
        );

        // Management Report Cache Observers
        Lead::observe(ReportCacheObserver::class);
        Project::observe(ReportCacheObserver::class);
        Invoice::observe(ReportCacheObserver::class);
        Payment::observe(ReportCacheObserver::class);
        Certificate::observe(ReportCacheObserver::class);
        ProjectAssignment::observe(ReportCacheObserver::class);
        Task::observe(ReportCacheObserver::class);
    }
}
