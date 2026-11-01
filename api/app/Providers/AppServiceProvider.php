<?php

namespace App\Providers;

use App\Modules\Certification\Listeners\IssueCertificateOnCompletion;
use App\Modules\Commerce\Events\OrderPaid;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Policies\OrderPolicy;
use App\Modules\Learning\Events\EnrollmentCompleted;
use App\Modules\Learning\Listeners\ActivateEnrollment;
use App\Modules\Learning\Models\Enrollment;
use App\Modules\Learning\Policies\EnrollmentPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        $this->configureModules();
        $this->configureRateLimiting();
        $this->configureAuthLinks();
    }

    /**
     * Antar modul hanya berkomunikasi lewat event (blueprint §3).
     */
    private function configureModules(): void
    {
        Event::listen(OrderPaid::class, ActivateEnrollment::class);
        Event::listen(EnrollmentCompleted::class, IssueCertificateOnCompletion::class);

        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Enrollment::class, EnrollmentPolicy::class);
    }

    /**
     * Rate limit: login 5/menit/IP, checkout 10/menit/user, publik 120/menit/IP.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }

    /**
     * Link di email mengarah ke halaman Nuxt, bukan ke API.
     */
    private function configureAuthLinks(): void
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');

        VerifyEmail::createUrlUsing(function (object $notifiable) use ($frontend) {
            $signed = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())],
                absolute: false,
            );

            return $frontend.'/verifikasi-email?'.http_build_query(['url' => $signed]);
        });

        ResetPassword::createUrlUsing(fn (object $notifiable, string $token) => $frontend.'/reset-password?'.http_build_query([
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]));
    }
}
