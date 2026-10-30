<?php

namespace App\Providers;

use App\Contracts\CodeSender;
use App\Enums\ContactRequestStatus;
use App\Services\Messaging\LogCodeSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Seul l'envoi dans le journal existe pour l'instant ; WhatsApp et
        // SMS Orange seront choisis ici selon config('idee.otp.driver').
        $this->app->bind(CodeSender::class, LogCodeSender::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Limite les envois de codes pour éviter les abus et les coûts de SMS.
        RateLimiter::for('otp', fn (Request $request) => [
            Limit::perMinute(1)->by('phone:'.$request->input('phone')),
            Limit::perHour(10)->by('ip:'.$request->ip()),
        ]);

        RateLimiter::for('otp-verify', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Nombre de demandes reçues qui attendent une réponse, affiché dans la navigation.
        View::composer('components.layout', function (ViewInstance $view) {
            $user = request()->user();

            $view->with('pendingRequestsCount', $user
                ? $user->receivedContactRequests()->whereIn('status', [ContactRequestStatus::Envoyee, ContactRequestStatus::Lue])->count()
                : 0);
        });
    }
}
