@props(['groupe'])

<a href="{{ route('laboratoire.groupes.show', $groupe->id) }}" wire:navigate
    class="inline-flex items-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-bold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300">
    Voir détail
</a>
