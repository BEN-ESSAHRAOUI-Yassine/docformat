<?php

namespace App\Providers;

use App\Services\Ai\GroqProvider;
use App\Services\Ai\LocalHeuristicProvider;
use App\Services\Ai\ProviderManager;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProviderManager::class, function ($app) {
            $manager = new ProviderManager;

            $groqConfig = config('services.ai.groq');
            if (! empty($groqConfig['api_key'])) {
                $manager->add(new GroqProvider(
                    $groqConfig['api_key'],
                    $groqConfig['base_url'],
                    $groqConfig['model'],
                ));
            }

            $manager->add(new LocalHeuristicProvider);

            return $manager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
