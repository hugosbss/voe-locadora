<?php

namespace App\Providers;

use App\Models\ClientRegistration;
use App\Models\QuotaType;
use App\Models\User;
use App\Models\Vehicle;
use App\Policies\ClientRegistrationPolicy;
use App\Policies\QuotaTypePolicy;
use App\Policies\VehiclePolicy;
use App\Support\Totp;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Totp::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRateLimiters();

        $this->registerAuthorization();
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('cep', function (Request $request): Limit {
            return Limit::perMinute((int) config('rate.limits.cep_per_minute', 30))
                ->by((string) $request->ip());
        });

        RateLimiter::for('admin_login', function (Request $request): Limit {
            $email = strtolower(trim((string) $request->input('email', '')));

            return Limit::perMinute((int) config('rate.limits.login_per_minute', 5))
                ->by($request->ip().'|'.hash('sha256', $email));
        });

        RateLimiter::for('admin_password', function (Request $request): Limit {
            $policy = config('rate.limits.password_reset');

            return Limit::perMinutes(
                (int) ($policy['decay_minutes'] ?? 10),
                (int) ($policy['max_attempts'] ?? 6),
            )->by((string) $request->ip());
        });
    }

    private function registerAuthorization(): void
    {
        Gate::policy(ClientRegistration::class, ClientRegistrationPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(QuotaType::class, QuotaTypePolicy::class);

        // Gestão de usuários administrativos: restrita ao papel admin.
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());

        // Habilita o helper `Route::model()` para evitar rota curinga
        // `/documents/{...}`: o binding é sempre por modelo/autorização.
        Route::model('registration', ClientRegistration::class);
    }
}
