<?php

namespace Navin\ExceptionNotifier;

use Illuminate\Support\ServiceProvider;
use Navin\ExceptionNotifier\Commands\TestExceptionMailerCommand;
use Navin\ExceptionNotifier\Commands\InstallCommand;

class ExceptionNotifierServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Auto-register everything on boot
        $this->autoRegister();
        
        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'exception-notifier');
        
        // Merge config with defaults
        $this->mergeConfigFrom(
            __DIR__.'/../config/exception-notifier.php', 'exception-notifier'
        );

        if ($this->app->runningInConsole()) {
            // Publish config file
            $this->publishes([
                __DIR__.'/../config/exception-notifier.php' => base_path('config/exception-notifier.php'),
            ], 'config');
            
            $this->commands([
                TestExceptionMailerCommand::class,
                InstallCommand::class,
            ]);
        }
    }

    public function register()
    {
        // Load config first
        $this->mergeConfigFrom(
            __DIR__.'/../config/exception-notifier.php', 'exception-notifier'
        );
        
        $this->app->singleton(ExceptionMailer::class, function ($app) {
            $config = $app['config']['exception-notifier'] ?? [];
            return new ExceptionMailer($config);
        });

        $this->app->alias(ExceptionMailer::class, 'exception-mailer');
    }

    protected function autoRegister()
    {
        // Auto-inject into existing exception handler
        $this->injectIntoExceptionHandler();
    }

    protected function injectIntoExceptionHandler()
    {
        // Get the current exception handler
        $handler = $this->app->make('Illuminate\Contracts\Debug\ExceptionHandler');
        
        // Wrap it with our mailer
        $this->app->singleton('Illuminate\Contracts\Debug\ExceptionHandler', function ($app) use ($handler) {
            return new class($handler, $app->make(ExceptionMailer::class)) extends \Laravel\Lumen\Exceptions\Handler {
                protected $originalHandler;
                protected $mailer;
                
                public function __construct($originalHandler, $mailer)
                {
                    $this->originalHandler = $originalHandler;
                    $this->mailer = $mailer;
                }
                
                public function report(\Exception $e)
                {
                    // Send email
                    try {
                        $this->mailer->send($e);
                    } catch (\Exception $mailException) {
                        // Silently fail if email sending fails
                    }
                    
                    // Call original handler
                    return $this->originalHandler->report($e);
                }
                
                public function render($request, \Exception $e)
                {
                    return $this->originalHandler->render($request, $e);
                }
            };
        });
    }
}
