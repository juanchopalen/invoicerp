@php
    $tenant = auth()->user()?->tenant;
@endphp
@if (filled($tenant))
    <div class="me-3 flex min-h-8 items-center">
        <x-filament::badge color="gray">
            {{ $tenant->name }}
        </x-filament::badge>
    </div>
@endif
