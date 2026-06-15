@props(['statut'])

@php
    $colorClass = match ($statut) {
        'en attente' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        'en cours' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
        'terminé' => 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300',
        'bloqué' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
        default => 'bg-gray-100 text-gray-700 dark:bg-gray-500/15 dark:text-gray-300',
    };
@endphp

<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $colorClass }}">
    {{ ucfirst($statut) }}
</span>
