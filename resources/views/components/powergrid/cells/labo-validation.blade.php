@props(['validation'])

@if ($validation)
    <div>
        <p class="font-medium text-slate-900 dark:text-white">
            {{ \Illuminate\Support\Carbon::parse($validation)->format('d/m/Y') }}
        </p>
        <p class="text-slate-500 dark:text-slate-400">
            {{ \Illuminate\Support\Carbon::parse($validation)->format('H:i') }}
        </p>
    </div>
@else
    <span
        class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
        Non datée
    </span>
@endif
