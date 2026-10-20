<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Queue\Console\WorkCommand;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->shareCheckAuthSubscription();
        $this->configureQueueWorker();
    }

    private function configureQueueWorker(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->callAfterResolving(WorkCommand::class, static function (WorkCommand $command): void {
            $command->getDefinition()->getOption('memory')->setDefault((int) config('queue.worker.memory', 0));
        });

        Event::listen(CommandStarting::class, static function (CommandStarting $event): void {
            if ($event->command === 'queue:work') {
                ini_set('memory_limit', (string) config('queue.worker.php_memory_limit', '-1'));
            }
        });
    }

    private function shareCheckAuthSubscription(): void
    {
        View::composer('*', fn ($view) => $view->with('checkAuthAndSubscription', checkAuthAndSubscription())
        );
    }
}
