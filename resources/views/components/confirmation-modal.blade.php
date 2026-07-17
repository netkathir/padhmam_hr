@props(['id' => 'confirmAction', 'title' => 'Confirm Action', 'message' => 'Are you sure?', 'form' => null])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">{{ $message }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" @if($form) form="{{ $form }}" @endif class="btn btn-danger">{{ $slot }}</button>
            </div>
        </div>
    </div>
</div>
