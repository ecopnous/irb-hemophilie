@props([
    'definition',
    'wireKey',
    'present' => null,
])

<div
    class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
    <div class="mb-4">
        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $definition->label }}</p>
        @if (filled($definition->description))
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $definition->description }}</p>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-select.styled label="Réponse" wire:model.live="premierSignesForm.{{ $wireKey }}.present"
            placeholder="Laisser vide pour plus tard..." :options="[['label' => 'Oui', 'value' => 1], ['label' => 'Non', 'value' => 0]]" />

        @if ((int) $present === 1)
            <x-number :label="$definition->value_label . ' (optionnel)'"
                wire:model="premierSignesForm.{{ $wireKey }}.value" min="0"
                placeholder="Ex. âge ou nombre..." />
        @else
            <div class="flex items-end">
                <p class="pb-2 text-xs italic text-slate-500 dark:text-slate-400">
                    {{ $definition->value_label }} affiché uniquement si la réponse est Oui.
                </p>
            </div>
        @endif

        <x-input label="Commentaire (optionnel)" wire:model="premierSignesForm.{{ $wireKey }}.comment"
            placeholder="Précisions cliniques..." />
    </div>
</div>
