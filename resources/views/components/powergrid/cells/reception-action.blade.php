@props(['consultation'])

@switch($consultation->type)
    @case('depistage')
        <a href="{{ route('consultation.show', $consultation->id) }}" wire:navigate
            class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:border-emerald-500/40">
            Résultat
        </a>
        @break

    @case('consultation')
        @if ($consultation->user_id !== null && $consultation->issue === null)
            <a href="{{ route('consultation.show', $consultation->id) }}" wire:navigate
                class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:border-emerald-500/40">
                Consulter
            </a>
        @elseif ($consultation->user_id === null)
            <a href="{{ route('consultation.prelevement', $consultation->id) }}" wire:navigate
                class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50/80 px-3 py-2 text-xs font-bold text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-100">
                Orienter
            </a>
        @else
            <span
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
                Déjà Cloturée
            </span>
        @endif
        @break

    @default
        <span
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
            <span class="h-2 w-2 rounded-full bg-slate-300 dark:bg-slate-600"></span>
            Aucunne Action
        </span>
@endswitch
