@props([
    'area',
    'label',
    'description',
    'route',
    'icon' => null,
    'badge' => null,
    'badgeValue' => null,
])

@php
    $canAccess = nav_can($area);
    $shouldHide = ! $canAccess && \App\Support\GradeNavigation::shouldHideOnDashboard($area);
@endphp

@if (! $shouldHide)
    @if ($canAccess)
        <a href="{{ route($route) }}" wire:navigate
            {{ $attributes->class([
                'group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-cyan-200 hover:bg-cyan-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-cyan-500/30 dark:hover:bg-cyan-500/10',
            ]) }}>
            <div>
                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $label }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $description }}</p>
            </div>
            @if ($badgeValue !== null)
                <span
                    class="rounded-full bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">{{ $badgeValue }}</span>
            @elseif ($icon === 'clipboard-document-list')
                <flux:icon.clipboard-document-list class="h-5 w-5 text-cyan-600 dark:text-cyan-300" />
            @elseif ($icon === 'briefcase')
                <flux:icon.briefcase class="h-5 w-5 text-cyan-600 dark:text-cyan-300" />
            @endif
        </a>
    @else
        <div
            {{ $attributes->class([
                'flex cursor-not-allowed items-center justify-between rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-3 opacity-60 dark:border-slate-700 dark:bg-slate-900/40',
            ]) }}
            title="Accès non autorisé pour votre grade">
            <div>
                <p class="text-sm font-bold text-slate-500 dark:text-slate-400">{{ $label }}</p>
                <p class="text-xs text-slate-400 dark:text-slate-500">{{ $description }}</p>
            </div>
            @if ($badgeValue !== null)
                <span
                    class="rounded-full bg-slate-100 px-3 py-1 text-sm font-black text-slate-400 dark:bg-slate-800 dark:text-slate-500">{{ $badgeValue }}</span>
            @elseif ($icon === 'clipboard-document-list')
                <flux:icon.clipboard-document-list class="h-5 w-5 text-slate-400" />
            @elseif ($icon === 'briefcase')
                <flux:icon.briefcase class="h-5 w-5 text-slate-400" />
            @endif
        </div>
    @endif
@endif
