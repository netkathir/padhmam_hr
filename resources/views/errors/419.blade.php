<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>419 - Session Expired</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5 text-center">
    <h1 class="display-4 fw-semibold">Session Expired</h1>
    <p class="text-muted">Your session has expired. Please sign in again.</p>
    <a href="{{ route('login') }}" class="btn btn-primary">Return to Login</a>
</div>
</body>
</html>
