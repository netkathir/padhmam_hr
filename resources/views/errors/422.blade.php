<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>422 - Validation Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5 text-center">
    <h1 class="display-4 fw-semibold">Validation Error</h1>
    <p class="text-muted">Please check the submitted form and try again.</p>
    <a href="{{ url()->previous() }}" class="btn btn-primary">Go Back</a>
</div>
</body>
</html>
