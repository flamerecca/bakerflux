<?php

namespace Flamerecca\Bakerflow;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;

class BakerflowServiceProvider extends ServiceProvider
{
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->registerConsoleCommands();
        }
    }
    
    public function boot(Router $router, Dispatcher $event)
    {
        if (config('app.env') == 'testing') {
            $this->loadMigrationsFrom(realpath(__DIR__.'/migrations'));
        }

        $this->loadMigrationsFrom(realpath(__DIR__.'/../migrations'));
    }

    /**
     * Register the commands accessible from the Console.
     */
    private function registerConsoleCommands()
    {
        $this->commands(Commands\InstallCommand::class);
        $this->commands(Commands\BakeEntityCommand::class);
        $this->commands(Commands\BakeControllerWithServiceCommand::class);
        $this->commands(Commands\BakeMongoEntityCommand::class);
    }
}
