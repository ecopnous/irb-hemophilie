@props(['prelevement'])

@if ($prelevement)
    <div class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
        <p class="font-medium text-slate-900 dark:text-white">
            {{ \Illuminate\Support\Carbon::parse($prelevement)->format('d/m/Y') }}
        </p>
        <p class="text-slate-500 dark:text-slate-400">
            {{ \Illuminate\Support\Carbon::parse($prelevement)->format('H:i:s') }}
        </p>
    </div>
@else
    <span
        class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
        En attente
    </span>
@endif
