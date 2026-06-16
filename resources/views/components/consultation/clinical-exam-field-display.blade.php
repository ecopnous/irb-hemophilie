@props([
    'definition',
    'answer' => null,
])

<div class="flex items-start justify-between gap-3 border-b border-slate-100 py-2.5 last:border-b-0 dark:border-slate-800">
    <p class="text-sm text-slate-600 dark:text-slate-300">{{ $definition->label }}</p>
    <p class="shrink-0 text-right text-sm font-semibold text-slate-900 dark:text-white">
        {{ $answer?->displaySummary() ?? '—' }}
    </p>
</div>
