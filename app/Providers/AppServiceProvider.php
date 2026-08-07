<?php

namespace App\Providers;

use App\Contracts\CameraProviderInterface;
use App\Models\CommandCenterIncident;
use App\Models\Setting;
use App\Policies\CommandCenterPolicy;
use App\Services\CommandCenter\DummyCameraProvider;
use App\Services\Dashboard\DatabaseDashboardMetrics;
use App\Services\Dashboard\DashboardMetricsContract;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Dashboard service — swap DatabaseDashboardMetrics for an API-backed
        // implementation when Immigration/TM30 system integrations are ready.
        $this->app->bind(DashboardMetricsContract::class, DatabaseDashboardMetrics::class);

        // Camera provider — swap DummyCameraProvider for an ONVIF/RTSP/NVR/VMS
        // backed implementation once physical CCTV hardware is connected.
        $this->app->bind(CameraProviderInterface::class, DummyCameraProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(CommandCenterIncident::class, CommandCenterPolicy::class);

        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            return route('internal.password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });

        // The site name (Internal Portal > Settings > Website Settings) was
        // previously only ever passed to the header/footer by HomeController,
        // so it silently fell back to its hardcoded default on every other
        // public page. A composer guarantees it's available wherever the
        // header/footer are rendered, without every controller needing to
        // remember to pass it.
        View::composer(['public.layouts.header', 'public.layouts.footer'], function ($view) {
            $view->with('siteName', Setting::get('general.site_name', 'ID5 @ Thailand 5.0'));
        });
    }
}
