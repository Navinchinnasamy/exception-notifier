<?php

namespace Navin\ExceptionNotifier\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'exception-notifier:install';
    protected $description = 'Install the exception notifier';

    public function handle()
    {
        $this->info('🚀 Installing Exception Notifier...');

        // For Lumen, provide manual setup instructions
        if (str_contains(app()->version(), 'Lumen')) {
            $this->info('📋 Lumen Setup Instructions:');
            $this->line('');
            $this->line('1. Add to bootstrap/app.php:');
            $this->line('   $app->register(Navin\ExceptionNotifier\ExceptionNotifierServiceProvider::class);');
            $this->line('   $app->configure(\'exception-notifier\');');
            $this->line('');
            $this->line('2. Copy config file:');
            $this->line('   cp vendor/navin/exception-notifier/config/exception-notifier.php config/');
            $this->line('');
            $this->line('3. Add to .env:');
            $this->line('   EXCEPTION_MAILER_ENABLED=true');
            $this->line('   EXCEPTION_MAILER_TO=your-email@company.com');
            $this->line('');
        } else {
            $this->info('✅ Laravel auto-discovery handles installation automatically!');
            $this->line('');
            $this->line('Just add to your .env file:');
            $this->line('EXCEPTION_MAILER_TO=your-email@company.com');
            $this->line('');
        }

        $this->info('📧 Don\'t forget to configure your mail settings!');
        $this->info('🧪 Test with: php artisan exception-notifier:test');
        $this->info('✅ Installation complete!');
    }
}
