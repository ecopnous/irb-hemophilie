@props([
    'definition',
    'wireKey',
    'present' => null,
    'fieldType' => null,
])

@php
    $type = $fieldType ?? $definition->field_type->value;
@endphp

<div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-900/40">
    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $definition->label }}</p>
    @if (filled($definition->description))
        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $definition->description }}</p>
    @endif

    <div class="mt-3">
        @if ($type === 'boolean')
            <x-select.styled label="Réponse" wire:model.live="clinicalExamForm.{{ $wireKey }}.present"
                placeholder="Non renseigné" :options="[['label' => 'Oui', 'value' => 1], ['label' => 'Non', 'value' => 0]]" />
        @elseif ($type === 'boolean_with_note')
            <div class="grid gap-3 md:grid-cols-2">
                <x-select.styled label="Réponse" wire:model.live="clinicalExamForm.{{ $wireKey }}.present"
                    placeholder="Non renseigné" :options="[['label' => 'Oui', 'value' => 1], ['label' => 'Non', 'value' => 0]]" />
                @if ((int) $present === 1)
                    <x-input :label="$definition->value_label ? 'Mesure (' . $definition->value_label . ')' : 'Précision'"
                        wire:model="clinicalExamForm.{{ $wireKey }}.note"
                        placeholder="Ex. 2" />
                @endif
            </div>
        @elseif ($type === 'number')
            <x-number :label="$definition->value_label ? $definition->label . ' (' . $definition->value_label . ')' : $definition->label"
                wire:model="clinicalExamForm.{{ $wireKey }}.value_number" min="0" step="0.1" />
        @else
            <x-input label="Valeur" wire:model="clinicalExamForm.{{ $wireKey }}.value_text"
                placeholder="Saisir l'observation..." />
        @endif
    </div>
</div>
