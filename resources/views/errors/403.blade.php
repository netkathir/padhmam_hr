<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Access Denied</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5 text-center">
                    <h1 class="display-5 fw-semibold">Access Denied</h1>
                    <p class="text-muted mb-4">You do not have permission to access this page.</p>
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
