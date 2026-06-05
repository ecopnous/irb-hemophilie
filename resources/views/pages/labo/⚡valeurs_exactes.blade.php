<?php

use App\Models\Configs\Acte;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Valeurs normales laboratoire'), Layout('layouts::app.other.laboratoire')] class extends Component {
    #[Url(as: 'acte')]
    public ?int $selectedActeId = null;

    public ?Acte $selectedActe = null;

    public array $form = [
        'unite' => null,
        'min' => null,
        'max' => null,
        'homme_min' => null,
        'homme_max' => null,
        'femme_min' => null,
        'femme_max' => null,
    ];

    public function mount(): void
    {
        $this->loadSelectedActe();
    }

    public function updatedSelectedActeId(): void
    {
        $this->loadSelectedActe();
    }

    public function loadSelectedActe(): void
    {
        if (!$this->selectedActeId) {
            $this->selectedActe = null;
            $this->resetForm();

            return;
        }

        $this->selectedActe = Acte::query()
            ->with(['service', 'departement'])
            ->whereHas('departement', fn($query) => $query->where('ref', 'labo'))
            ->find($this->selectedActeId);

        if (!$this->selectedActe) {
            $this->selectedActeId = null;
            $this->resetForm();

            return;
        }

        $this->form = [
            'unite' => $this->selectedActe->unite,
            'min' => $this->selectedActe->min,
            'max' => $this->selectedActe->max,
            'homme_min' => $this->selectedActe->homme_min,
            'homme_max' => $this->selectedActe->homme_max,
            'femme_min' => $this->selectedActe->femme_min,
            'femme_max' => $this->selectedActe->femme_max,
        ];
    }

    public function resetForm(): void
    {
        $this->form = [
            'unite' => null,
            'min' => null,
            'max' => null,
            'homme_min' => null,
            'homme_max' => null,
            'femme_min' => null,
            'femme_max' => null,
        ];
    }

    public function clearSelection(): void
    {
        $this->selectedActeId = null;
        $this->selectedActe = null;
        $this->resetForm();
    }

    public function reloadSelectedActe(): void
    {
        $this->loadSelectedActe();
    }

    public function saveSelectedActe(): void
    {
        abort_unless($this->selectedActeId, 404);

        $validated = $this->validate([
            'form.unite' => ['nullable', 'string', 'max:50'],
            'form.min' => ['nullable', 'numeric'],
            'form.max' => ['nullable', 'numeric'],
            'form.homme_min' => ['nullable', 'numeric'],
            'form.homme_max' => ['nullable', 'numeric'],
            'form.femme_min' => ['nullable', 'numeric'],
            'form.femme_max' => ['nullable', 'numeric'],
        ]);

        Acte::whereKey($this->selectedActeId)->update($validated['form']);

        $this->loadSelectedActe();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Valeurs de l\'acte mises à jour avec succès.',
        );
    }
};
?>

<div class="space-y-6">
    <section
        class="overflow-hidden rounded-[2rem] border border-cyan-100 bg-gradient-to-br from-white via-cyan-50/40 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="flex flex-col gap-6 px-5 py-6 sm:px-6 lg:px-8 lg:py-8">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl space-y-3">
                    <div
                        class="inline-flex w-fit items-center gap-2 rounded-full border border-cyan-200 bg-white/80 px-3 py-1 text-[11px] font-black uppercase tracking-[0.24em] text-cyan-700 shadow-sm dark:border-cyan-400/20 dark:bg-slate-900/70 dark:text-cyan-300">
                        <span class="h-2 w-2 rounded-full bg-cyan-500"></span>
                        Biologie clinique
                    </div>

                    <div class="space-y-2">
                        <h1 class="text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">
                            Valeurs exactes du laboratoire
                        </h1>
                        <p class="max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300 sm:text-base">
                            Consultez tous les actes dans un tableau PowerGrid avec recherche et filtres, puis ouvrez
                            une fiche dediee pour modifier un seul acte a la fois.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:min-w-[28rem]">
                    <div
                        class="rounded-3xl border border-white/70 bg-white/80 px-4 py-4 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/75">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Mode</p>
                        <p class="mt-2 text-sm font-black text-slate-950 dark:text-white">Edition individuelle</p>
                    </div>
                    <div
                        class="rounded-3xl border border-cyan-100 bg-cyan-50/90 px-4 py-4 shadow-sm dark:border-cyan-400/20 dark:bg-cyan-500/10">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-cyan-700 dark:text-cyan-300">
                            Tableau</p>
                        <p class="mt-2 text-sm font-black text-cyan-950 dark:text-cyan-100">PowerGrid filtrable</p>
                    </div>
                    <div
                        class="rounded-3xl border border-slate-200/80 bg-slate-50/90 px-4 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Acte courant</p>
                        <p class="mt-2 truncate text-sm font-black text-slate-950 dark:text-white">
                            {{ $selectedActe?->name ?? 'Aucun acte selectionne' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 2xl:grid-cols-[minmax(0,1.45fr)_420px]">
        <section
            class="min-w-0 rounded-[1.75rem] border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
            <div class="mb-4 flex flex-col gap-3 border-b border-slate-200 pb-4 dark:border-slate-800 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-1">
                    <h2 class="text-lg font-black text-slate-950 dark:text-white">Referentiel des actes</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Recherchez, filtrez, puis cliquez sur <span class="font-bold">Modifier</span> pour ouvrir la
                        fiche d un acte.
                    </p>
                </div>

                @if ($selectedActe)
                    <div
                        class="inline-flex items-center gap-2 rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300">
                        <span class="h-2 w-2 rounded-full bg-cyan-500"></span>
                        Acte selectionne : {{ $selectedActe->name }}
                    </div>
                @endif
            </div>

            <livewire:labo-valeurs-actes-table :selectedActeId="$selectedActeId" />
        </section>

        <aside class="min-w-0">
            <section
                class="sticky top-4 overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div
                    class="border-b border-slate-200/80 bg-gradient-to-r from-slate-50 to-cyan-50 px-4 py-4 dark:border-slate-800 dark:from-slate-900 dark:to-slate-900 sm:px-5">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Fiche de modification</p>
                    <h3 class="mt-2 text-xl font-black text-slate-950 dark:text-white">
                        {{ $selectedActe?->name ?? 'Selectionnez un acte' }}
                    </h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Les modifications enregistrees ici concernent uniquement l acte actuellement selectionne.
                    </p>
                </div>

                @if ($selectedActe)
                    <div class="space-y-5 p-4 sm:p-5">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/60">
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Service</p>
                                <p class="mt-2 text-sm font-bold text-slate-900 dark:text-white">
                                    {{ $selectedActe->service?->name ?? 'Aucun service' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/60">
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Departement</p>
                                <p class="mt-2 text-sm font-bold text-slate-900 dark:text-white">
                                    {{ ucfirst($selectedActe->departement?->name ?? 'Laboratoire') }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <section class="rounded-3xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-950/50">
                                <div class="mb-4">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Reference generale</p>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Unite et bornes communes.</p>
                                </div>

                                <div class="grid gap-3">
                                    <x-input wire:model.defer="form.unite" label="Unite" placeholder="mg/L" />
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <x-number wire:model.defer="form.min" label="Valeur min" step="0.01" />
                                        <x-number wire:model.defer="form.max" label="Valeur max" step="0.01" />
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-3xl border border-blue-100 bg-blue-50/70 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                                <div class="mb-4">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">Profil homme</p>
                                    <p class="mt-1 text-sm text-blue-900/70 dark:text-blue-100/70">Bornes specifiques si elles different de la reference generale.</p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <x-number wire:model.defer="form.homme_min" label="Homme min" step="0.01" />
                                    <x-number wire:model.defer="form.homme_max" label="Homme max" step="0.01" />
                                </div>
                            </section>

                            <section class="rounded-3xl border border-rose-100 bg-rose-50/70 p-4 dark:border-rose-500/20 dark:bg-rose-500/10">
                                <div class="mb-4">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-rose-700 dark:text-rose-300">Profil femme</p>
                                    <p class="mt-1 text-sm text-rose-900/70 dark:text-rose-100/70">Bornes specifiques si elles different de la reference generale.</p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <x-number wire:model.defer="form.femme_min" label="Femme min" step="0.01" />
                                    <x-number wire:model.defer="form.femme_max" label="Femme max" step="0.01" />
                                </div>
                            </section>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 dark:border-slate-800 sm:flex-row">
                            <x-button icon="arrow-path" text="Recharger" flat color="secondary"
                                wire:click="reloadSelectedActe" />
                            <x-button icon="x-mark" text="Fermer la fiche" flat color="secondary"
                                wire:click="clearSelection" />
                            <div class="sm:ml-auto">
                                <flux:button icon="save" text="Enregistrer cet acte" variant="primary" color="cyan"
                                    wire:click="saveSelectedActe" />
                            </div>
                        </div>
                    </div>
                @else
                    <div class="p-5">
                        <div
                            class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center dark:border-slate-700 dark:bg-slate-950/60">
                            <p class="text-lg font-black text-slate-900 dark:text-white">Aucune selection</p>
                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                Utilisez le tableau a gauche pour choisir un acte, puis ses valeurs seront chargees
                                ici pour une modification individuelle.
                            </p>
                        </div>
                    </div>
                @endif
            </section>
        </aside>
    </div>
</div>
