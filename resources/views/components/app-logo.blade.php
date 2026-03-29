@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="{{ config('app.name', 'Laravel') }}" {{ $attributes }}>
    </flux:sidebar.brand>
@else
    <flux:brand name="{{ config('app.name', 'Laravel') }}" {{ $attributes }}>
    </flux:brand>
@endif
