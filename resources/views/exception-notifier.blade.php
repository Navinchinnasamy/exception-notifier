<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exception Alert</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .section { margin-bottom: 25px; }
        .section h3 { color: #333; border-bottom: 2px solid #dc3545; padding-bottom: 5px; }
        .info-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 10px; margin: 15px 0; }
        .info-label { font-weight: bold; color: #666; }
        .code-block { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 15px; font-family: monospace; white-space: pre-wrap; overflow-x: auto; }
        .stack-trace { max-height: 300px; overflow-y: auto; }
        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
            .container { margin: 10px; }
            body { padding: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Exception Alert</h1>
            <!-- <p>{{ $app_name }} - {{ strtoupper($environment) }}</p> -->
        </div>
        
        <div class="content">
            <div class="section">
                <h3>Exception Details</h3>
                <div class="info-grid">
                    <div class="info-label">Type:</div>
                    <div>{{ get_class($exception) }}</div>
                    <div class="info-label">Message:</div>
                    <div>{{ $exception->getMessage() }}</div>
                    <div class="info-label">File:</div>
                    <div>{{ $exception->getFile() }}</div>
                    <div class="info-label">Line:</div>
                    <div>{{ $exception->getLine() }}</div>
                    <div class="info-label">Time:</div>
                    <div>{{ $timestamp->format('Y-m-d H:i:s T') }}</div>
                </div>
            </div>

            @if($request)
            <div class="section">
                <h3>Request Information</h3>
                <div class="info-grid">
                    <div class="info-label">URL:</div>
                    <div>{{ $request->fullUrl() }}</div>
                    <div class="info-label">Method:</div>
                    <div>{{ $request->method() }}</div>
                    <div class="info-label">IP Address:</div>
                    <div>{{ $request->ip() }}</div>
                    <div class="info-label">User Agent:</div>
                    <div>{{ $request->userAgent() }}</div>
                </div>
            </div>
            @endif

            <div class="section">
                <h3>Stack Trace</h3>
                <div class="code-block stack-trace">{{ $exception->getTraceAsString() }}</div>
            </div>

            <div class="section">
                <h3>Environment Information</h3>
                <div class="info-grid">
                    <div class="info-label">Application:</div>
                    <div>{{ $app_name }}</div>
                    <div class="info-label">Environment:</div>
                    <div>{{ $environment }}</div>
                    <div class="info-label">PHP Version:</div>
                    <div>{{ PHP_VERSION }}</div>
                    @if($hash)
                    <div class="info-label">Exception Hash:</div>
                    <div>{{ $hash }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
