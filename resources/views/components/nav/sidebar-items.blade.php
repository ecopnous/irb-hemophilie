@props(['group' => 'main'])

@foreach (collect(config('navigation.sidebar', []))->where('group', $group) as $item)
    @if (nav_can($item['area']))
        <flux:sidebar.item :icon="$item['icon']" href="{{ route($item['route']) }}" wire:navigate>
            {{ $item['label'] }}
        </flux:sidebar.item>
    @endif
@endforeach
