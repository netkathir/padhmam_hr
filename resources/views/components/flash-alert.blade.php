@props(['type' => 'info', 'message' => null])

@if($message)
    <div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }} role="alert">
        {{ $message }}
    </div>
@endif
