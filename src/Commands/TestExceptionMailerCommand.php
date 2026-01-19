<?php

namespace Navin\ExceptionNotifier\Commands;

use Illuminate\Console\Command;
use Navin\ExceptionNotifier\ExceptionMailer;
use Exception;

class TestExceptionMailerCommand extends Command
{
    protected $signature = 'exception-notifier:test';
    protected $description = 'Test the exception notifier configuration';

    public function handle()
    {
        $this->info('Testing Exception Notifier Configuration...');

        try {
            $mailer = app(ExceptionMailer::class);
            $testException = new Exception('This is a test exception from the exception notifier');
            
            $this->info('Configuration check:');
            $config = config('exception-notifier');
            $this->line('- Enabled: ' . ($config['enabled'] ? 'Yes' : 'No'));
            $this->line('- Recipients: ' . implode(', ', $config['mail']['to']));
            $this->line('- From: ' . $config['mail']['from']);
            $this->line('- From Name: ' . $config['mail']['from_name']);
            $this->line('- Environment: ' . app()->environment());
            $this->line('- Version detection: ' . (app()->version()));
            $this->line('- Using: ' . ($mailer->isModernVersion() ? 'Modern Mail' : 'Legacy Swift'));
            $this->line('- SMTP Host: ' . config('mail.host', 'not set'));
            $this->line('- SMTP Port: ' . config('mail.port', 'not set'));
            $this->line('- SMTP User: ' . config('mail.username', 'not set'));
            
            $result = $mailer->sendTest($testException);
            
            if ($result) {
                $this->info('✅ Test email sent successfully!');
                $this->info('Check your email inbox for the exception notification.');
            } else {
                $this->error('❌ Test email failed to send.');
                $this->error('Check your mail configuration.');
            }
        } catch (Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}
