<?php

namespace App\Providers;

use App\Contracts\CameraProviderInterface;
use App\Models\CommandCenterIncident;
use App\Models\Setting;
use App\Policies\CommandCenterPolicy;
use App\Services\CommandCenter\DummyCameraProvider;
use App\Services\Dashboard\DatabaseDashboardMetrics;
use App\Services\Dashboard\DashboardMetricsContract;
use App\Support\CmsText;
use App\Support\CmsTranslator;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Blade;
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

        // The wording editors change in WordPress is layered over the language
        // files by a translator that otherwise behaves exactly like Laravel's.
        CmsText::reset();

        $this->app->extend('translator', function ($translator) {
            $cms = new CmsTranslator($translator->getLoader(), $translator->getLocale());
            $cms->setFallback($translator->getFallback());

            return $cms;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(CommandCenterIncident::class, CommandCenterPolicy::class);

        // `@cmsEdit($model)` marks the element that follows it as editable in
        // WordPress. It prints nothing unless the visitor is a website editor
        // in edit mode, so the public markup is unchanged for everyone else.
        Blade::directive('cmsEdit', fn (string $expression) => "<?php echo \\App\\Support\\CmsEdit::marker({$expression}); ?>");

        // `@cmsAdd($target, $label)` draws a "+" tile that opens a new item's
        // form in WordPress. Like @cmsEdit it prints nothing for visitors.
        Blade::directive('cmsAdd', fn (string $expression) => "<?php echo \\App\\Support\\CmsEdit::addTile({$expression}); ?>");

        // `@jsText(__('key'))` prints a line of wording as a JavaScript string
        // that stays valid whatever an editor types into it.
        Blade::directive('jsText', fn (string $expression) => "<?php echo \\App\\Support\\CmsText::js({$expression}); ?>");

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
        // The hotline number is printed on a dozen pages. It used to be typed
        // into each of them; it now comes from the one setting editors change
        // in WordPress (ข้อมูลเว็บไซต์ > สายด่วน), read once per request.
        View::composer('public.*', function ($view) {
            static $hotline = null;

            $view->with('hotline', $hotline ??= (string) Setting::get('general.emergency_phone', '1178'));
        });

        View::composer(['public.layouts.header', 'public.layouts.footer'], function ($view) {
            $view->with('siteName', Setting::get('general.site_name', 'ID5 @ Thailand 5.0'));
        });
    }
}
