@props(['datetime', 'timeFormat' => 'H:i:s'])

<div>
    <p class="font-medium text-slate-900 dark:text-white">
        {{ optional($datetime)->format('d/m/Y') }}
    </p>
    <p class="text-slate-500 dark:text-slate-400">
        {{ optional($datetime)->format($timeFormat) }}
    </p>
</div>
