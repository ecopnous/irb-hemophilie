@props([
    'label',
    'value',
    'suffix' => '',
    'tone' => 'slate',
    'icon' => 'chart-bar',
])

@php
    $tones = [
        'slate' => 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900',
        'blue' => 'border-blue-200 bg-blue-50/80 dark:border-blue-500/20 dark:bg-blue-500/10',
        'emerald' => 'border-emerald-200 bg-emerald-50/80 dark:border-emerald-500/20 dark:bg-emerald-500/10',
        'amber' => 'border-amber-200 bg-amber-50/80 dark:border-amber-500/20 dark:bg-amber-500/10',
        'rose' => 'border-rose-200 bg-rose-50/80 dark:border-rose-500/20 dark:bg-rose-500/10',
        'cyan' => 'border-cyan-200 bg-cyan-50/80 dark:border-cyan-500/20 dark:bg-cyan-500/10',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border p-4 shadow-sm ' . ($tones[$tone] ?? $tones['slate'])]) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">
                {{ $value }}<span class="text-sm font-semibold text-slate-500">{{ $suffix }}</span>
            </p>
        </div>
        <div class="rounded-xl bg-white/70 p-2 dark:bg-black/20">
            <flux:icon :icon="$icon" class="size-5 text-slate-500" />
        </div>
    </div>
</div>
