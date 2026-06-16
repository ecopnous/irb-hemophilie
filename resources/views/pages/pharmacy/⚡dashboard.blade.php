<?php

use App\Services\PharmacyDashboardService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Tableau de bord pharmacie'), Layout('layouts::app.other.pharmacy')] class extends Component {
    private function service(): PharmacyDashboardService
    {
        return app(PharmacyDashboardService::class);
    }

    #[Computed]
    public function overview(): array
    {
        return $this->service()->overview(current_hopital_id());
    }

    #[Computed]
    public function pendingPrescriptions()
    {
        return $this->service()->pendingPrescriptions(current_hopital_id());
    }

    #[Computed]
    public function lowStockItems()
    {
        return $this->service()->lowStockItems(current_hopital_id());
    }

    #[Computed]
    public function recentMovements()
    {
        return $this->service()->recentMovements(current_hopital_id());
    }

    public function movementLabel(string $type): string
    {
        return match ($type) {
            'in' => 'Entree',
            'out' => 'Sortie',
            'depreciation' => 'Depreciation',
            'adjustment' => 'Ajustement',
            default => ucfirst($type),
        };
    }

    public function movementClass(string $type): string
    {
        return match ($type) {
            'in' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
            'out' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
            'depreciation' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        };
    }

    public function prescriptionStatusMeta(?string $status): array
    {
        return match ($status) {
            'served' => ['label' => 'Servie', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'],
            'partial' => ['label' => 'Partielle', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300'],
            'cancelled' => ['label' => 'Annulee', 'class' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300'],
            default => ['label' => 'Brouillon', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'],
        };
    }
};
?>

<div class="mx-auto space-y-6 max-w-7xl">
    <div class="grid gap-6 xl:grid-cols-[1.5fr,1fr]">
        <div class="space-y-5">
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Pharmacie', 'icon' => 'building-storefront'],
            ]" />

            <div class="space-y-3">
                <p class="text-xs font-black uppercase tracking-[0.28em] text-emerald-700 dark:text-emerald-300">
                    Gestion pharmaceutique
                </p>
                <div class="space-y-2">
                    <h1 class="max-w-3xl text-3xl font-black tracking-tight text-slate-900 dark:text-white md:text-4xl">
                        Tableau de bord pharmacie
                    </h1>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-400 md:text-base">
                        Pilotez les prescriptions, surveillez les stocks critiques et suivez les mouvements
                        pour garantir la disponibilite des medicaments a l'hopital.
                    </p>
                </div>
            </div>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-3xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">A traiter</p>
                    <p class="mt-3 text-3xl font-black text-amber-900 dark:text-amber-100">{{ $this->overview['prescriptions_pending'] }}</p>
                    <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-300/80">Prescriptions en attente de dispensation</p>
                </div>

                <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Aujourd'hui</p>
                    <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->overview['prescriptions_today'] }}</p>
                    <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Nouvelles prescriptions</p>
                </div>

                <div class="rounded-3xl border border-red-200 bg-red-50/80 p-5 shadow-sm dark:border-red-500/20 dark:bg-red-500/10">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-red-700 dark:text-red-300">Stock critique</p>
                    <p class="mt-3 text-3xl font-black text-red-900 dark:text-red-100">{{ $this->overview['critical_stock'] }}</p>
                    <p class="mt-1 text-xs text-red-700/80 dark:text-red-300/80">Lignes sous le seuil minimum</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Servies</p>
                    <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $this->overview['prescriptions_served'] }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Prescriptions totalement dispensees</p>
                </div>

                <div class="rounded-3xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Mouvements</p>
                    <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $this->overview['movements_today'] }}</p>
                    <p class="mt-1 text-xs text-sky-700/80 dark:text-sky-300/80">Operations enregistrees aujourd'hui</p>
                </div>

                <div class="rounded-3xl border border-violet-200 bg-violet-50/80 p-5 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-700 dark:text-violet-300">Valeur stock</p>
                    <p class="mt-3 text-3xl font-black text-violet-900 dark:text-violet-100">
                        {{ number_format($this->overview['stock_value'], 0, ',', ' ') }}
                    </p>
                    <p class="mt-1 text-xs text-violet-700/80 dark:text-violet-300/80">Unites x montant pivot</p>
                </div>
            </section>
        </div>

        <div class="rounded-[1.75rem] border border-slate-200/70 bg-white/90 p-5 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-950/70">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Raccourcis</p>
                    <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Modules pharmacie</h2>
                </div>
                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                    <flux:icon.building-storefront class="h-5 w-5" />
                </div>
            </div>

            <div class="mt-5 grid gap-3">
                <a href="{{ route('pharmacie.prescriptions') }}" wire:navigate
                    class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-amber-200 hover:bg-amber-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-amber-500/30 dark:hover:bg-amber-500/10">
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Prescriptions</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">A dispenser en priorite</p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-sm font-black text-amber-700 shadow-sm dark:bg-slate-800 dark:text-amber-300">{{ $this->overview['prescriptions_pending'] }}</span>
                </a>

                <a href="{{ route('pharmacie.stock') }}" wire:navigate
                    class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-red-200 hover:bg-red-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-red-500/30 dark:hover:bg-red-500/10">
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Stock medicaments</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Alertes et inventaire</p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-sm font-black text-red-700 shadow-sm dark:bg-slate-800 dark:text-red-300">{{ $this->overview['critical_stock'] }}</span>
                </a>

                <a href="{{ route('pharmacie.movements') }}" wire:navigate
                    class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-sky-200 hover:bg-sky-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-sky-500/30 dark:hover:bg-sky-500/10">
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Mouvements</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Entrees et sorties du jour</p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-sm font-black text-sky-700 shadow-sm dark:bg-slate-800 dark:text-sky-300">{{ $this->overview['movements_today'] }}</span>
                </a>

                <a href="{{ route('pharmacie.depreciations') }}" wire:navigate
                    class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-amber-200 hover:bg-amber-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-amber-500/30 dark:hover:bg-amber-500/10">
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Deprecies</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Perimes et retires</p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-sm font-black text-amber-700 shadow-sm dark:bg-slate-800 dark:text-amber-300">{{ $this->overview['expired'] }}</span>
                </a>

                <a href="{{ route('pharmacie.medicaments') }}" wire:navigate
                    class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-emerald-200 hover:bg-emerald-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-500/30 dark:hover:bg-emerald-500/10">
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Medicaments</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Catalogue actif</p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-sm font-black text-emerald-700 shadow-sm dark:bg-slate-800 dark:text-emerald-300">{{ $this->overview['medicaments'] }}</span>
                </a>

                <a href="{{ route('pharmacie.pharmacies') }}" wire:navigate
                    class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-violet-200 hover:bg-violet-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-violet-500/30 dark:hover:bg-violet-500/10">
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Pharmacies</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Points de dispensation</p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-sm font-black text-violet-700 shadow-sm dark:bg-slate-800 dark:text-violet-300">{{ $this->overview['pharmacies'] }}</span>
                </a>
            </div>
        </div>
    </div>

    <section class="grid gap-4 lg:grid-cols-4">
        <div class="rounded-[1.75rem] border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">Entrees</p>
            <h3 class="mt-2 text-lg font-black text-emerald-900 dark:text-emerald-100">Reapprovisionnement</h3>
            <p class="mt-2 text-sm leading-6 text-emerald-800/80 dark:text-emerald-200/80">
                Aujourd'hui : <span class="font-bold">{{ $this->overview['today_in'] }}</span> |
                Semaine : <span class="font-bold">{{ $this->overview['week_in'] }}</span> |
                Mois : <span class="font-bold">{{ $this->overview['month_in'] }}</span>
            </p>
        </div>

        <div class="rounded-[1.75rem] border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-sky-700 dark:text-sky-300">Sorties</p>
            <h3 class="mt-2 text-lg font-black text-sky-900 dark:text-sky-100">Dispensation</h3>
            <p class="mt-2 text-sm leading-6 text-sky-800/80 dark:text-sky-200/80">
                Aujourd'hui : <span class="font-bold">{{ $this->overview['today_out'] }}</span> |
                Semaine : <span class="font-bold">{{ $this->overview['week_out'] }}</span> |
                Mois : <span class="font-bold">{{ $this->overview['month_out'] }}</span>
            </p>
        </div>

        <div class="rounded-[1.75rem] border border-amber-200 bg-amber-50/80 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-700 dark:text-amber-300">Expiration</p>
            <h3 class="mt-2 text-lg font-black text-amber-900 dark:text-amber-100">Surveillance qualite</h3>
            <p class="mt-2 text-sm leading-6 text-amber-800/80 dark:text-amber-200/80">
                Perimes : <span class="font-bold">{{ $this->overview['expired'] }}</span> |
                Sous 90 jours : <span class="font-bold">{{ $this->overview['expiring_soon'] }}</span>
            </p>
        </div>

        <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Activite</p>
            <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white">Volume mensuel</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                Prescriptions : <span class="font-bold text-slate-900 dark:text-white">{{ $this->overview['prescriptions_month'] }}</span> |
                Ruptures : <span class="font-bold text-red-600">{{ $this->overview['out_of_stock'] }}</span>
            </p>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Priorite</p>
                    <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Prescriptions en attente</h2>
                </div>
                <flux:button href="{{ route('pharmacie.prescriptions') }}" wire:navigate size="sm" variant="ghost">
                    Voir tout
                </flux:button>
            </div>

            @if ($this->pendingPrescriptions->isEmpty())
                <div class="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                    Aucune prescription en attente pour le moment.
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($this->pendingPrescriptions as $index => $prescription)
                        @php($status = $this->prescriptionStatusMeta($prescription->status))
                        <div class="flex items-center gap-4 px-5 py-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {{ $index + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold text-slate-900 dark:text-white">
                                    {{ $prescription->dossierPatient?->full_name ?: 'Patient inconnu' }}
                                </p>
                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                    {{ $prescription->reference }} · {{ $prescription->medicaments->count() }} medicament(s)
                                </p>
                            </div>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $status['class'] }}">
                                {{ $status['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Alerte</p>
                    <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Stocks critiques</h2>
                </div>
                <flux:button href="{{ route('pharmacie.stock') }}" wire:navigate size="sm" variant="ghost">
                    Gerer le stock
                </flux:button>
            </div>

            @if ($this->lowStockItems->isEmpty())
                <div class="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                    Tous les stocks sont au-dessus du seuil minimum.
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($this->lowStockItems as $index => $item)
                        <div class="flex items-center gap-4 px-5 py-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs font-black text-red-700 dark:bg-red-500/15 dark:text-red-300">
                                {{ $index + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold text-slate-900 dark:text-white">{{ $item->name }}</p>
                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                    {{ $item->pharmacie_nom }} · Ref. {{ $item->reference ?: '-' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-red-600 dark:text-red-400">{{ (int) $item->quantiter }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">min. {{ (int) $item->stock_min }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <section class="rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 md:flex-row md:items-center md:justify-between dark:border-slate-800">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Suivi operationnel</p>
                <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Derniers mouvements de stock</h2>
            </div>
            <flux:button href="{{ route('pharmacie.movements') }}" wire:navigate size="sm" variant="ghost">
                Historique complet
            </flux:button>
        </div>

        @if ($this->recentMovements->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                Aucun mouvement enregistre pour cet hopital.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500 dark:bg-slate-900 dark:text-slate-400">
                        <tr>
                            <th class="px-5 py-3">#</th>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3">Medicament</th>
                            <th class="px-5 py-3">Pharmacie</th>
                            <th class="px-5 py-3 text-right">Qte</th>
                            <th class="px-5 py-3">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($this->recentMovements as $index => $movement)
                            <tr class="text-slate-700 dark:text-slate-300">
                                <td class="px-5 py-3 font-semibold text-slate-500">{{ $index + 1 }}</td>
                                <td class="px-5 py-3">{{ optional($movement->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $this->movementClass($movement->movement_type) }}">
                                        {{ $this->movementLabel($movement->movement_type) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">{{ $movement->medicament?->name ?: '-' }}</td>
                                <td class="px-5 py-3">{{ $movement->pharmacie?->nom ?: '-' }}</td>
                                <td class="px-5 py-3 text-right font-bold">{{ $movement->quantity }}</td>
                                <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ $movement->reference ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section>
        <div class="mb-4 px-1">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Registre</p>
            <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Toutes les prescriptions</h2>
        </div>
        <livewire:pharmacy-prescription-table />
    </section>
</div>
