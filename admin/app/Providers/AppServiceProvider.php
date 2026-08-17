<?php

namespace App\Providers;

use App\Common;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->configurePublicUrls();
        $this->loadSystemSettingClasses();

        try {
            View::share('company', app()->runningInConsole() ? null : Common::getCompanyByHost());
        } catch (\Throwable $e) {
            View::share('company', null);
            \Log::warning('Admin company share failed: ' . $e->getMessage());
        }
    }

    /**
     * Production classmap-authoritative autoload can miss newly added classes.
     */
    protected function loadSystemSettingClasses(): void
    {
        $files = [
            app_path('Services/SystemSettingService.php'),
            app_path('Http/Controllers/Admin/SystemSettingController.php'),
        ];
        foreach ($files as $file) {
            if (is_file($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Keep generated asset URLs on /admin when the app runs from admin/public.
     */
    protected function configurePublicUrls(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        $request = request();
        if (!$request) {
            return;
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (!preg_match('#(/admin)/public/index\.php$#', $scriptName, $matches)) {
            return;
        }

        $detectedRoot = rtrim($request->getSchemeAndHttpHost(), '/') . $matches[1];
        $configuredRoot = rtrim((string) config('app.url'), '/');

        if ($configuredRoot !== $detectedRoot) {
            URL::forceRootUrl($detectedRoot);
        }
    }
}
