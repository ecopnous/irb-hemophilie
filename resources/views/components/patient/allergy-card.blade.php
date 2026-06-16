@props(['allergy'])

<div
    class="rounded-2xl border border-rose-200/80 bg-rose-50/40 p-4 dark:border-rose-500/20 dark:bg-rose-950/15">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $allergy->displayName() }}</p>
                <span
                    class="rounded-full border border-rose-200 bg-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-500/30 dark:bg-rose-950/40 dark:text-rose-200">
                    {{ $allergy->typeLabel() }}
                </span>
                @if ($allergy->isActive())
                    <span
                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        Active
                    </span>
                @else
                    <span
                        class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        Terminée
                    </span>
                @endif
            </div>
            @if (filled($allergy->description))
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $allergy->description }}</p>
            @endif
        </div>
        <div class="flex shrink-0 gap-1">
            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openAllergyModal({{ $allergy->id }})"
                x-on:click="$tsui.open.modal('allergy-modal')" />
            <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDeleteAllergy({{ $allergy->id }})" />
        </div>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Symptômes</p>
            <p class="mt-1 text-sm text-slate-800 dark:text-slate-100">{{ $allergy->symptome }}</p>
        </div>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Conduite à tenir</p>
            <p class="mt-1 text-sm text-slate-800 dark:text-slate-100">{{ $allergy->solution }}</p>
        </div>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Début</p>
            <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">
                {{ $allergy->date_debut?->format('d/m/Y') ?? '—' }}
            </p>
        </div>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Fin</p>
            <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">
                {{ $allergy->date_fin?->format('d/m/Y') ?? '—' }}
            </p>
        </div>
    </div>
</div>
