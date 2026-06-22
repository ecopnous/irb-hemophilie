@props([
    'definition',
    'wireKey',
    'present' => null,
    'fieldType' => null,
])

@php
    $type = $fieldType ?? $definition->field_type->value;
    $presentValue = $present === null || $present === '' ? null : (int) $present;
@endphp

<div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-900/40">
    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $definition->label }}</p>
    @if (filled($definition->description))
        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $definition->description }}</p>
    @endif

    <div class="mt-3">
        @if ($type === 'boolean' || $type === 'boolean_with_note')
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Réponse</p>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="$set('clinicalExamForm.{{ $wireKey }}.present', null)"
                        @class([
                            'rounded-xl border px-3 py-2 text-xs font-bold transition',
                            'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => $presentValue === null,
                            'border-slate-200 bg-slate-100 text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400' => $presentValue !== null,
                        ])
                    >
                        Non renseigné
                    </button>
                    <button
                        type="button"
                        wire:click="$set('clinicalExamForm.{{ $wireKey }}.present', 1)"
                        @class([
                            'rounded-xl border px-3 py-2 text-xs font-bold transition',
                            'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/15 dark:text-emerald-300' => $presentValue === 1,
                            'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => $presentValue !== 1,
                        ])
                    >
                        Oui
                    </button>
                    <button
                        type="button"
                        wire:click="$set('clinicalExamForm.{{ $wireKey }}.present', 0)"
                        @class([
                            'rounded-xl border px-3 py-2 text-xs font-bold transition',
                            'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-500/40 dark:bg-rose-500/15 dark:text-rose-300' => $presentValue === 0,
                            'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => $presentValue !== 0,
                        ])
                    >
                        Non
                    </button>
                </div>
            </div>

            @if ($type === 'boolean_with_note' && $presentValue === 1)
                <div class="mt-3">
                    <x-input :label="$definition->value_label ? 'Mesure (' . $definition->value_label . ')' : 'Précision'"
                        wire:model="clinicalExamForm.{{ $wireKey }}.note"
                        placeholder="Ex. 2" />
                </div>
            @endif
        @elseif ($type === 'number')
            <x-number :label="$definition->value_label ? $definition->label . ' (' . $definition->value_label . ')' : $definition->label"
                wire:model="clinicalExamForm.{{ $wireKey }}.value_number" min="0" step="0.1" />
        @else
            <x-input wire:model="clinicalExamForm.{{ $wireKey }}.value_text"
                placeholder="Saisir l'observation..." />
        @endif
    </div>
</div>
