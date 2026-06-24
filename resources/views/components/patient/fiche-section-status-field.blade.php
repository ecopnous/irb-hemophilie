@props(['section'])

<div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/40">
    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Statut de la section</p>
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
        Indiquez si cette section est complète ou nécessite encore des informations.
    </p>
    <flux:radio.group wire:model="sectionCompletionStatus.{{ $section }}" variant="segmented" class="mt-3">
        <flux:radio value="1">Complet</flux:radio>
        <flux:radio value="0">Incomplet</flux:radio>
    </flux:radio.group>
</div>
