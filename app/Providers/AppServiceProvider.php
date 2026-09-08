<?php

namespace App\Providers;

use App\Services\ChatGTPService;
use App\Services\ShortDrama\ShortDramaPluginInstaller;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->useLangPath($this->app->basePath('lang'));

        $this->app->singleton(ShortDramaPluginInstaller::class);

        ini_set('memory_limit', '512M');
        if ($this->app->environment('local')) {

            $this->app->singleton(ChatGTPService::class, function ($app) {
                return new ChatGTPService();
            });

        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        // Password policy (A07): min length, letters, numbers
        Password::defaults(function () {
            return Password::min(8)->letters()->numbers();
        });


        if(env('APP_ENV') === 'production') {
            Event::listen(CommandStarting::class, function (CommandStarting $event) {
                if ((dbConnectionStatus() && Schema::hasTable('users') && file_exists(storage_path('installed')))) {
                    if (in_array($event->command, ['migrate:fresh', 'db:wipe', 'db:seed'])) {
                        throw new \Exception("❌ Command '{$event->command}' is blocked in production.");
                    }
                }
            });
        }

        Paginator::useBootstrap();

        Blade::directive('hasPermission', function ($permissions) {
            return "<?php if(Auth::user()->can({$permissions})): ?>";
        });

        Blade::directive('endhasPermission', function () {
            return '<?php endif; ?>';
        });

        $this->app->singleton('translation.loader', function ($app) {
            return new CustomTranslationLoader($app['files'], $app->langPath());
        });

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            $locale = $app['config']['app.locale'];

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }
}
