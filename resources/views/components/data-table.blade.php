<div class="table-responsive">
    <table {{ $attributes->merge(['class' => 'table table-hover align-middle']) }}>
        {{ $slot }}
    </table>
</div>
