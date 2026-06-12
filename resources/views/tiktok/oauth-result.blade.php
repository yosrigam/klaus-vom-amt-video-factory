<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TikTok OAuth</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
        code, pre { background: #f4f4f5; padding: 0.75rem; border-radius: 6px; display: block; overflow-x: auto; }
        .error { color: #b91c1c; }
        .ok { color: #15803d; }
    </style>
</head>
<body>
    <h1>TikTok OAuth</h1>

    @if (! $success)
        <p class="error"><strong>Failed:</strong> {{ $message }}</p>
        <p>Run <code>php artisan tiktok:authorize</code> and complete the flow again immediately after approving.</p>
    @else
        <p class="ok"><strong>Success.</strong> Add these to your <code>.env</code>:</p>
        <pre>TIKTOK_ACCESS_TOKEN={{ $accessToken }}
TIKTOK_REFRESH_TOKEN={{ $refreshToken }}</pre>
        <p>Expires in {{ $expiresIn }} seconds. Scope: {{ $scope }}</p>
        <p>Redirect URI used: <code>{{ $redirectUri }}</code></p>
    @endif
</body>
</html>
