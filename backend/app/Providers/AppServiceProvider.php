<?php

namespace App\Providers;

use App\Contracts\CodeSender;
use App\Enums\ContactRequestStatus;
use App\Models\User;
use App\Services\Journeys\JourneyService;
use App\Services\Messaging\FallbackCodeSender;
use App\Services\Messaging\LogCodeSender;
use App\Services\Messaging\MessageService;
use App\Services\Messaging\OrangeSmsCodeSender;
use App\Services\Messaging\WhatsAppCodeSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        // Canal d'envoi des codes de connexion, choisi par IDEE_OTP_DRIVER.
        $this->app->bind(CodeSender::class, fn () => match (config('idee.otp.driver')) {
            'sms' => new OrangeSmsCodeSender,
            'whatsapp' => new WhatsAppCodeSender,
            'whatsapp_sms' => new FallbackCodeSender([new WhatsAppCodeSender, new OrangeSmsCodeSender]),
            default => new LogCodeSender,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Équipe IDEE : modération, vérifications, fiches et indicateurs (AD-01 à AD-04).
        Gate::define('administrer', fn (User $user) => $user->isAdmin());

        // Limite les envois de codes pour éviter les abus et les coûts de SMS.
        RateLimiter::for('otp', fn (Request $request) => [
            Limit::perMinute(1)->by('phone:'.$request->input('phone')),
            Limit::perHour(10)->by('ip:'.$request->ip()),
        ]);

        RateLimiter::for('otp-verify', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Demandes reçues qui attendent une réponse et rappels de parcours échus, affichés dans la navigation.
        View::composer('components.layout', function (ViewInstance $view) {
            $user = request()->user();

            $view->with('pendingRequestsCount', $user
                ? $user->receivedContactRequests()->whereIn('status', [ContactRequestStatus::Envoyee, ContactRequestStatus::Lue])->count()
                    + app(MessageService::class)->unreadCount($user)
                : 0);
            $view->with('dueRemindersCount', $user ? app(JourneyService::class)->dueRemindersCount($user) : 0);
            $view->with('unreadNotificationsCount', $user ? $user->unreadNotifications()->count() : 0);
        });
    }
}
