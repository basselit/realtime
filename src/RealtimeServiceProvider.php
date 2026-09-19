<?php

namespace Codatsoft\Realtime;

use Codatsoft\Realtime\Console\VoiceTokenCommand;
use Codatsoft\Realtime\Contracts\AuthorizesCalls;
use Codatsoft\Realtime\Contracts\BuildsDispatchMetadata;
use Codatsoft\Realtime\Contracts\ResolvesVoiceUser;
use Codatsoft\Realtime\Support\AllowAllCalls;
use Codatsoft\Realtime\Support\DefaultDispatchMetadata;
use Codatsoft\Realtime\Support\GuardVoiceUserResolver;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the package into a product: config, migration, routes, the artisan command,
 * the default implementations of the three extension points, and the per-user
 * broadcast channel. A product overrides an extension point by binding its own
 * implementation of the contract in its own service provider.
 */
class RealtimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/realtime.php', 'realtime');

        $this->app->bindIf(ResolvesVoiceUser::class, GuardVoiceUserResolver::class);
        $this->app->bindIf(AuthorizesCalls::class, AllowAllCalls::class);
        $this->app->bindIf(BuildsDispatchMetadata::class, DefaultDispatchMetadata::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/realtime.php' => config_path('realtime.php'),
        ], 'realtime-config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->registerRoutes();
        $this->registerUserChannel();

        if ($this->app->runningInConsole())
        {
            $this->commands([VoiceTokenCommand::class]);
        }
    }

    protected function registerRoutes(): void
    {
        if ($this->app->routesAreCached())
        {
            return;
        }

        $prefix = (string) config('realtime.routes.prefix', 'api');

        Route::prefix($prefix)
            ->middleware((array) config('realtime.routes.middleware', ['api', 'auth:sanctum']))
            ->group(__DIR__ . '/../routes/voice.php');

        Route::prefix($prefix)
            ->middleware((array) config('realtime.routes.webhook_middleware', ['api']))
            ->group(__DIR__ . '/../routes/webhook.php');
    }

    /**
     * The private channel a product's events broadcast on: `user.<attribute>`. Only the
     * user whose attribute matches the channel parameter may subscribe.
     */
    protected function registerUserChannel(): void
    {
        if (!config('realtime.broadcasting.register_user_channel', true))
        {
            return;
        }

        $attribute = (string) config('realtime.broadcasting.user_attribute', 'web_id');

        Broadcast::channel(
            (string) config('realtime.broadcasting.user_channel', 'user.{userId}'),
            fn ($user, string $userId): bool => (string) ($user->{$attribute} ?? '') === $userId
        );
    }

}
