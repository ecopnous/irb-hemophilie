@props(['consultation'])

<div class="space-y-1">
    <p class="uppercase tracking-tight">
        {{ ucwords($consultation->departement?->name ?? ' - ') }}
    </p>
    @if ($consultation->is_clore)
        <p class="text-xs font-medium text-green-600 dark:text-green-300">
            dossier classé
        </p>
    @else
        <p class="text-xs font-medium text-red-600 dark:text-red-300">
            dossier ouvert
        </p>
    @endif
</div>
