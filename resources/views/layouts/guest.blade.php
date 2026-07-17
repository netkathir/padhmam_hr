<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('hrms.app_name') }} | {{ $title ?? 'Authentication' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; background: linear-gradient(135deg, #102a43, #1f4e79 60%, #f5f7fb 60%); }
        .auth-shell { min-height: 100vh; display: grid; place-items: center; padding: 2rem; }
        .auth-card { width: min(100%, 480px); border: 0; border-radius: 1.25rem; overflow: hidden; }
    </style>
</head>
<body>
<main class="auth-shell">
    <div class="card shadow-lg auth-card">
        <div class="card-body p-4 p-md-5">
            @yield('content')
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (form.dataset.submitted === 'true') {
                return;
            }
            form.dataset.submitted = 'true';
            form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                button.disabled = true;
                var spinner = button.querySelector('[data-loading-spinner]');
                if (spinner) {
                    spinner.classList.remove('d-none');
                }
            });
        });
    });
</script>
</body>
</html>
