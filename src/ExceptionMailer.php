<?php

namespace Navin\ExceptionNotifier;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class ExceptionMailer
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function sendTest(Exception $exception, Request $request = null): bool
    {
        // For testing, still respect grouping if enabled
        if (!$this->shouldSend($exception)) {
            return false;
        }

        $hash = $this->getExceptionHash($exception);

        // Check grouping for test too
        if ($this->config['grouping']['enabled'] && $this->isGrouped($hash)) {
            return false;
        }

        try {
            $this->sendEmail($exception, $request, $hash);
            $this->recordSent($hash);
            return true;
        } catch (Exception $e) {
            Log::error('Exception notifier test failed: ' . $e->getMessage());
            return false;
        }
    }

    public function send(Exception $exception, Request $request = null): bool
    {
        if (!$this->shouldSend($exception)) {
            return false;
        }

        $hash = $this->getExceptionHash($exception);

        // Database logging
        if ($this->config['database_logging']['enabled']) {
            $this->logToDatabase($exception, $request, $hash);
        }

        // Rate limiting
        if ($this->config['rate_limiting']['enabled'] && $this->isRateLimited($hash)) {
            return false;
        }

        // Exception grouping
        if ($this->config['grouping']['enabled'] && $this->isGrouped($hash)) {
            return false;
        }

        try {
            $this->sendEmail($exception, $request, $hash);
            $this->recordSent($hash);
            return true;
        } catch (Exception $e) {
            Log::error('Exception notifier failed: ' . $e->getMessage());
            return false;
        }
    }

    public function isModernVersion(): bool
    {
        return $this->isModernLaravel();
    }

    public function shouldSendTest(Exception $exception): bool
    {
        return $this->shouldSend($exception);
    }

    protected function shouldSend(Exception $exception): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }

        if (!in_array(app()->environment(), $this->config['environments']['enabled'])) {
            return false;
        }

        foreach ($this->config['excluded_exceptions'] as $ignoredException) {
            if (is_string($ignoredException) && $exception instanceof $ignoredException) {
                return false;
            }
        }

        if (empty($this->config['mail']['to'])) {
            return false;
        }

        return true;
    }

    protected function sendEmail(Exception $exception, Request $request = null, string $hash = null)
    {
        $data = [
            'exception' => $exception,
            'request' => $request,
            'hash' => $hash,
            'environment' => app()->environment(),
            'timestamp' => $this->now(),
            'app_name' => env('APP_NAME', 'Unknown Application'),
        ];

        // Version-compatible email sending
        if ($this->isModernLaravel()) {
            $this->sendModernEmail($data);
        } else {
            $this->sendLegacyEmail($data);
        }
    }

    protected function sendModernEmail(array $data)
    {
        Mail::send('exception-notifier::exception-notifier', $data, function ($message) {
            $message->to($this->config['mail']['to'])
                   ->subject($this->getSubject())
                   ->from($this->config['mail']['from'], $this->config['mail']['from_name']);

            if (!empty($this->config['mail']['cc'])) {
                $message->cc($this->config['mail']['cc']);
            }
        });
    }

    protected function sendLegacyEmail(array $data)
    {
        // Create Swift Mailer using environment values
        $transport = new \Swift_SmtpTransport(
            env('MAIL_HOST', 'localhost'),
            env('MAIL_PORT', 587),
            env('MAIL_ENCRYPTION') ?: 'tls'
        );
        
        if (env('MAIL_USERNAME')) {
            $transport->setUsername(env('MAIL_USERNAME'))
                     ->setPassword(env('MAIL_PASSWORD'));
        }
        
        $mailer = new \Swift_Mailer($transport);
        
        // Render HTML template
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
                .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: #dc3545; color: white; padding: 10px 20px; border-radius: 8px 8px 0 0; }
                .header h1 { margin: 0; font-size: 18px; }
                .content { padding: 20px; }
                .exception-info { background: #f8f9fa; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0; }
                .trace { background: #f8f9fa; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; overflow-x: auto; }
                .meta { display: flex; justify-content: space-between; margin: 15px 0; }
                .meta-item { background: #e9ecef; padding: 10px; border-radius: 4px; flex: 1; margin: 0 5px; }
                .footer { background: #f8f9fa; padding: 15px; text-align: center; color: #6c757d; border-radius: 0 0 8px 8px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚨 Exception Alert</h1>
                </div>
                <div class='content'>
                    <div class='exception-info'>
                        <h3>Exception Details</h3>
                        <p><strong>Message:</strong> {$data['exception']->getMessage()}</p>
                        <p><strong>File:</strong> {$data['exception']->getFile()}</p>
                        <p><strong>Line:</strong> {$data['exception']->getLine()}</p>
                        <p><strong>Type:</strong> " . get_class($data['exception']) . "</p>
                    </div>
                    
                    <div class='meta'>
                        <div class='meta-item'>
                            <strong>Time</strong><br>
                            {$data['timestamp']}
                        </div>
                        <div class='meta-item'>
                            <strong>Application</strong><br>
                            {$data['app_name']}
                        </div>
                        <div class='meta-item'>
                            <strong>Environment</strong><br>
                            {$data['environment']}
                        </div>
                    </div>
                    
                    <h3>Stack Trace</h3>
                    <div class='trace'>{$data['exception']->getTraceAsString()}</div>
                </div>
                <div class='footer'>
                    <p>This is an automated message from {$this->config['mail']['from_name']}</p>
                </div>
            </div>
        </body>
        </html>";

        // Create message with both HTML and plain text
        $plainText = "Exception Alert - {$data['app_name']}\n\n" .
                    "Environment: {$data['environment']}\n\n" .
                    "Exception: {$data['exception']->getMessage()}\n" .
                    "File: {$data['exception']->getFile()}:{$data['exception']->getLine()}\n" .
                    "Type: " . get_class($data['exception']) . "\n" .
                    "Time: {$data['timestamp']}\n\n" .
                    "Stack Trace:\n{$data['exception']->getTraceAsString()}";

        $message = \Swift_Message::newInstance()
            ->setSubject($this->getSubject())
            ->setFrom([$this->config['mail']['from'] => $this->config['mail']['from_name']])
            ->setTo($this->config['mail']['to'])
            ->setBody($html, 'text/html')
            ->addPart($plainText, 'text/plain');

        if (!empty($this->config['mail']['cc'])) {
            $message->setCc($this->config['mail']['cc']);
        }

        $result = $mailer->send($message);
        return true;
    }

    protected function isModernLaravel(): bool
    {
        // Check Laravel/Lumen version
        $version = app()->version();
        
        // Extract major version number
        preg_match('/(\d+)\./', $version, $matches);
        $majorVersion = isset($matches[1]) ? (int)$matches[1] : 5;
        
        // Use legacy for Lumen/Laravel 5.x, modern for 6+
        return $majorVersion >= 6;
    }

    protected function now()
    {
        // Carbon compatibility
        if (method_exists(Carbon::class, 'now')) {
            return Carbon::now();
        }
        return new Carbon();
    }

    protected function getSubject(): string
    {
        $prefix = str_replace('{ENV}', strtoupper(app()->environment()), $this->config['mail']['subject_prefix']);
        return $prefix . ' - ' . env('APP_NAME', 'Application');
    }

    protected function getExceptionHash(Exception $exception): string
    {
        return md5($exception->getFile() . $exception->getLine() . $exception->getMessage());
    }

    protected function isRateLimited(string $hash): bool
    {
        // Simple file-based rate limiting
        $cacheFile = storage_path('logs/exception_rate_' . $hash);
        
        if (!file_exists($cacheFile)) {
            return false;
        }

        $data = json_decode(file_get_contents($cacheFile), true);
        $timeWindow = $this->config['rate_limiting']['time_window'];
        $maxEmails = $this->config['rate_limiting']['max_emails'];

        if (time() - $data['first_sent'] > $timeWindow) {
            unlink($cacheFile);
            return false;
        }

        return $data['count'] >= $maxEmails;
    }

    protected function isGrouped(string $hash): bool
    {
        $cacheFile = storage_path('logs/exception_group_' . $hash);
        
        if (!file_exists($cacheFile)) {
            return false;
        }

        $lastSent = (int) file_get_contents($cacheFile);
        $groupWindow = $this->config['grouping']['time_window'];

        return (time() - $lastSent) < $groupWindow;
    }

    protected function recordSent(string $hash)
    {
        // Rate limiting record
        if ($this->config['rate_limiting']['enabled']) {
            $cacheFile = storage_path('logs/exception_rate_' . $hash);
            
            if (file_exists($cacheFile)) {
                $data = json_decode(file_get_contents($cacheFile), true);
                $data['count']++;
            } else {
                $data = ['first_sent' => time(), 'count' => 1];
            }
            
            file_put_contents($cacheFile, json_encode($data));
        }

        // Grouping record
        if ($this->config['grouping']['enabled']) {
            $cacheFile = storage_path('logs/exception_group_' . $hash);
            file_put_contents($cacheFile, time());
        }
    }

    protected function logToDatabase(Exception $exception, Request $request = null, string $hash = null)
    {
        // Database logging implementation would go here
        // This is a placeholder for future enhancement
    }
}
