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

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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
