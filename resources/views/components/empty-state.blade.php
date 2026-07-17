@props(['title' => 'No records found', 'message' => 'There is nothing to display yet.'])

<div class="text-center py-5">
    <div class="display-6 text-muted mb-2"><i class="bi bi-inbox"></i></div>
    <h3 class="h5">{{ $title }}</h3>
    <p class="text-muted mb-0">{{ $message }}</p>
</div>
