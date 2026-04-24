<?php

namespace App\Providers;

use App\Domain\Orders\Events\OrderStatusChanged;
use App\Listeners\SendOrderStatusNotifications;
use App\Listeners\UpdateInventory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Stripe SDK client — single shared instance, authenticated with the secret key.
        $this->app->singleton(StripeClient::class, function () {
            return new StripeClient((string) config('services.stripe.secret'));
        });
    }

    public function boot(): void
    {
        Event::listen(OrderStatusChanged::class, SendOrderStatusNotifications::class);
        Event::listen(OrderStatusChanged::class, UpdateInventory::class);

        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();
            if ($user?->isStaff()) {
                return Limit::perMinute(300)->by($user->id);
            }
            return Limit::perMinute(60)->by($user?->id ?: $request->ip());
        });
    }
}
