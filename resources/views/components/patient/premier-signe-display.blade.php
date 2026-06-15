@props([
    'definition',
    'answer' => null,
])

@php
    $present = $answer?->present;
    $isMissing = $present === null;
    $isPositive = $present === true;
@endphp

<div @class([
    'rounded-2xl border px-4 py-4',
    'border-slate-100 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900/40' => ! $isMissing,
    'border-amber-200/80 bg-amber-50/50 dark:border-amber-500/20 dark:bg-amber-950/10' => $isMissing,
])>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $definition->label }}</p>
            @if (filled($definition->description))
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $definition->description }}</p>
            @endif
        </div>

        @if ($isMissing)
            <span
                class="rounded-full border border-amber-300 bg-amber-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
                À compléter
            </span>
        @elseif ($isPositive)
            <span
                class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-950/30 dark:text-emerald-300">
                Oui
            </span>
        @else
            <span
                class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                Non
            </span>
        @endif
    </div>

    @if ($isPositive)
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">
                    {{ $definition->value_label }}
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                    @if ($answer?->value !== null)
                        {{ $answer->value }}
                        @if ($definition->value_type->unit() !== '')
                            <span class="font-medium text-slate-500">{{ $definition->value_type->unit() }}</span>
                        @endif
                    @else
                        <span class="italic text-slate-400">Non précisé</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Commentaire</p>
                <p @class([
                    'mt-1 text-sm leading-relaxed',
                    'text-slate-700 dark:text-slate-200' => filled($answer?->comment),
                    'italic text-slate-400' => ! filled($answer?->comment),
                ])>
                    {{ filled($answer?->comment) ? $answer->comment : 'Aucun commentaire' }}
                </p>
            </div>
        </div>
    @elseif (! $isMissing)
        <p class="mt-3 text-sm italic text-slate-500 dark:text-slate-400">
            Événement non rapporté pour ce patient.
            @if (filled($answer?->comment))
                <span class="not-italic text-slate-700 dark:text-slate-200">· {{ $answer->comment }}</span>
            @endif
        </p>
    @else
        <p class="mt-3 text-sm italic text-amber-700 dark:text-amber-300">
            Réponse Oui/Non non renseignée.
        </p>
    @endif
</div>
