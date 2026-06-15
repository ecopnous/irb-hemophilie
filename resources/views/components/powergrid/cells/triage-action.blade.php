@props(['consultation'])

<a href="{{ route('consultation.prelevement', $consultation->id) }}" wire:navigate
    class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50/80 px-3 py-2 text-xs font-bold text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-100">
    Prélever
</a>
