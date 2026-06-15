@props([
    'label',
    'value' => null,
    'missing' => false,
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'min-w-0 ' . $class]) }}>
    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">
        {{ $label }}
    </p>
    <p @class([
        'mt-1.5 text-sm font-semibold leading-relaxed',
        'italic text-amber-600 dark:text-amber-400' => $missing,
        'text-slate-900 dark:text-slate-100' => ! $missing,
    ])>
        {{ filled($value) ? $value : '—' }}
    </p>
</div>
