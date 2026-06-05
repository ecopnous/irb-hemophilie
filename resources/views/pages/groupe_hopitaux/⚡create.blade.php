<?php

use App\Models\Configs\GroupeHopital;
use App\Models\Configs\Hopital;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Title('Nouveau groupe d hopitaux')] class extends Component {
    use Interactions;

    public string $nom = '';
    public string $objetif = '';
    public string $note = '';
    public string $searchHopital = '';
    public array $selected_hopitaux = [];
    public Collection $hopitaux;

    public function mount(): void
    {
        $this->hopitaux = Hopital::query()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function hopitalOptions(): Collection
    {
        return $this->hopitaux
            ->when($this->searchHopital !== '', function (Collection $hopitaux) {
                $search = mb_strtolower($this->searchHopital);

                return $hopitaux->filter(function (Hopital $hopital) use ($search) {
                    return str_contains(mb_strtolower($hopital->name), $search)
                        || str_contains(mb_strtolower((string) $hopital->reference), $search)
                        || str_contains(mb_strtolower((string) $hopital->type), $search);
                });
            })
            ->values();
    }

    #[Computed]
    public function selectedHopitauxCollection(): Collection
    {
        return $this->hopitaux
            ->whereIn('id', $this->selected_hopitaux)
            ->sortBy('name')
            ->values();
    }

    #[Computed]
    public function completionStats(): array
    {
        $items = [
            filled($this->nom),
            filled($this->objetif),
            count($this->selected_hopitaux) > 0,
        ];

        return [
            'filled' => collect($items)->filter()->count(),
            'total' => count($items),
        ];
    }

    public function save(): void
    {
        try {
            $validated = $this->validate(
                [
                    'nom' => ['required', 'string', 'min:3', 'max:255'],
                    'objetif' => ['required', 'string', 'min:5', 'max:255'],
                    'note' => ['nullable', 'string', 'max:1000'],
                    'selected_hopitaux' => ['required', 'array', 'min:1'],
                    'selected_hopitaux.*' => ['integer', 'exists:hopitals,id'],
                ],
                [
                    'selected_hopitaux.required' => 'Selectionnez au moins un hopital.',
                    'selected_hopitaux.min' => 'Selectionnez au moins un hopital.',
                    'objetif.required' => 'L objectif du groupe est obligatoire.',
                ],
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dialog()->error('Formulaire incomplet', 'Veuillez renseigner les champs obligatoires du groupe.')->send();
            throw $e;
        }

        $groupe = DB::transaction(function () use ($validated) {
            $groupe = GroupeHopital::query()->create([
                'nom' => $validated['nom'],
                'objetif' => $validated['objetif'],
                'note' => $validated['note'] ?: null,
                'user_id' => auth()->id(),
            ]);

            $groupe->hopitaux()->sync($validated['selected_hopitaux']);

            return $groupe;
        });

        Flux::toast(variant: 'success', heading: 'Groupe enregistre', text: 'Le groupe d hopitaux a ete cree avec succes.');

        $this->redirectRoute('groupe_hopitaux.show', ['id' => $groupe->id], navigate: true);
    }
};
?>

<section class="w-full space-y-6">
    <x-header_default :title="__('Nouveau groupe d hopitaux')" :subtitle="__(
        'Structurez un reseau d etablissements autour d un objectif commun et d une liste d hopitaux rattaches.',
    )" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Groupes d hopitaux', 'link' => 'groupe_hopitaux.index', 'icon' => 'building-office-2'],
        ['label' => 'Nouveau groupe', 'icon' => 'plus'],
    ]">
        <x-slot:actions>
            <x-button icon="arrow-left" position="left" href="{{ route('groupe_hopitaux.index') }}" wire:navigate>
                Retour
            </x-button>
        </x-slot>
    </x-header_default>

    <form wire:submit="save" class="grid gap-6 px-4 pb-10 sm:px-6 lg:px-8 xl:grid-cols-[minmax(0,1.5fr)_24rem]">
        <div class="space-y-6">
            <x-card loading>
                <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Identite du groupe</p>
                        <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Informations principales</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Ces informations seront visibles dans la fiche detaillee du groupe et dans les tableaux de
                            pilotage.
                        </p>
                    </div>
                    <flux:badge color="sky" inset>Configuration</flux:badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <x-input wire:model="nom" label="Nom du groupe *" clearable
                        placeholder="Ex: Groupe hospitalier provincial" />
                    <x-input wire:model="objetif" label="Objectif *" clearable
                        placeholder="Ex: Coordination regionale des soins" />
                </div>

                <div class="mt-4">
                    <x-textarea wire:model="note" label="Note de gestion" maxlength="1000" count
                        placeholder="Contexte, remarques administratives, perimetre du groupe..." />
                </div>
            </x-card>

            <x-card loading>
                <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Etablissements</p>
                        <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Hopitaux rattaches</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Selectionnez tous les hopitaux qui appartiennent a ce groupe.
                        </p>
                    </div>

                    <div class="w-full md:max-w-sm">
                        <x-input wire:model.live.debounce.300ms="searchHopital" icon="magnifying-glass"
                            placeholder="Rechercher un hopital..." />
                    </div>
                </div>

                <x-select.styled label="Selection rapide des hopitaux *" wire:model="selected_hopitaux"
                    :options="$this->hopitalOptions" select="label:name|value:id" multiple searchable
                    hint="Vous pouvez rechercher puis selectionner plusieurs hopitaux." />

                <div class="mt-6 grid gap-3 md:grid-cols-2">
                    @forelse ($this->hopitalOptions as $hopital)
                        <label wire:key="hopital-option-{{ $hopital->id }}"
                            class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 transition hover:border-blue-300 hover:bg-blue-50/70 dark:border-slate-800 dark:bg-slate-900/60 dark:hover:border-blue-500/50 dark:hover:bg-blue-500/10">
                            <input type="checkbox" value="{{ $hopital->id }}" wire:model="selected_hopitaux"
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold text-slate-900 dark:text-white">
                                    {{ $hopital->name }}
                                </span>
                                <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
                                    {{ $hopital->reference }} · {{ ucfirst($hopital->type) }} ·
                                    {{ strtoupper($hopital->devise) }}
                                </span>
                            </span>
                        </label>
                    @empty
                        <div
                            class="md:col-span-2 rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Aucun hopital ne correspond a votre recherche.
                        </div>
                    @endforelse
                </div>
            </x-card>
        </div>

        <aside class="space-y-6">
            <div
                class="xl:sticky xl:top-6 space-y-6 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Apercu</p>
                    <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white">Resume du groupe</h3>
                </div>

                <div class="space-y-4">
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-900/70">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Nom</p>
                        <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                            {{ $nom ?: 'Nom non renseigne' }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-900/70">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Objectif</p>
                        <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">
                            {{ $objetif ?: 'Objectif non renseigne' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Hopitaux</p>
                            <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">
                                {{ count($selected_hopitaux) }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                            <p class="text-xs text-blue-700 dark:text-blue-300">Avancement</p>
                            <p class="mt-2 text-2xl font-black text-blue-950 dark:text-blue-100">
                                {{ $this->completionStats['filled'] }}/{{ $this->completionStats['total'] }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                            Selection actuelle
                        </p>
                        <div class="mt-3 space-y-2">
                            @forelse ($this->selectedHopitauxCollection as $hopital)
                                <div class="rounded-xl bg-slate-50 px-3 py-2 text-xs dark:bg-slate-900/70">
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $hopital->name }}</p>
                                    <p class="text-slate-500 dark:text-slate-400">{{ $hopital->reference }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Aucun hopital selectionne pour l instant.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                    <flux:button type="submit" variant="primary" color="blue" icon="save"
                        class="w-full justify-center" wire:loading.attr="disabled" wire:target="save">
                        Enregistrer le groupe
                    </flux:button>
                    <x-button href="{{ route('groupe_hopitaux.index') }}" wire:navigate class="justify-center" outline>
                        Annuler
                    </x-button>
                </div>
            </div>
        </aside>
    </form>
</section>
