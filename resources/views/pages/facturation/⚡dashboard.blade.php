<?php

use App\Services\ComptabiliteChartBuilder;
use App\Services\ComptabiliteMetricsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Tableau de bord comptabilité'), Layout('layouts::app.other.facturation')] class extends Component {
    public string $period = 'month';

    public ?string $date_start = null;

    public ?string $date_end = null;

    public function refreshData(): void
    {
        app(ComptabiliteMetricsService::class)->forgetDashboardCache(
            $this->period,
            $this->date_start,
            $this->date_end,
        );

        unset($this->metrics, $this->charts, $this->chartOptions);
    }

    #[Computed]
    public function metrics(): array
    {
        return app(ComptabiliteMetricsService::class)->dashboard(
            $this->period,
            $this->date_start,
            $this->date_end,
        );
    }

    #[Computed]
    public function charts(): array
    {
        return app(ComptabiliteChartBuilder::class)->fromMetrics($this->metrics);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function chartOptions(): array
    {
        return collect($this->charts)
            ->mapWithKeys(function ($chart) {
                $payload = json_decode($chart->toJson()->getContent(), true);

                return [$chart->id() => $payload['options']];
            })
            ->all();
    }

    public function money(float $value): string
    {
        return number_format($value, 0, ',', ' ');
    }
};
?>

@php($k = $this->metrics['kpis'])
@php($m = $this->metrics)
@php($charts = $this->charts)
@php($apexCdn = \ArielMejiaDev\LarapexCharts\LarapexChart::cdn().'/dist/apexcharts.min.js')

<div class="min-h-screen bg-[#eef2f7] dark:bg-slate-950" wire:key="comptabilite-{{ $period }}-{{ $date_start }}-{{ $date_end }}">

    <div class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
        <div class="mx-auto max-w-[1600px] px-4 py-5 lg:px-8">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <x-breadcrumbs :items="[
                        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                        ['label' => 'Comptabilité', 'icon' => 'banknotes'],
                    ]" />
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                        Tableau de bord comptabilité — {{ current_hopital_nom() }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ now()->translatedFormat('l d F Y') }} · {{ $m['periodLabel'] }}
                    </p>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex flex-wrap gap-2">
                        @foreach (['today' => "Aujourd'hui", 'week' => 'Semaine', 'month' => 'Mois', 'year' => 'Année', 'custom' => 'Personnalisée'] as $key => $label)
                            <button type="button" wire:click="$set('period', '{{ $key }}')"
                                class="rounded-xl px-3 py-2 text-xs font-bold transition {{ $period === $key ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    @if ($period === 'custom')
                        <input type="date" wire:model.live="date_start" class="rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                        <input type="date" wire:model.live="date_end" class="rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                    @endif

                    <flux:button variant="ghost" icon="arrow-path" wire:click="refreshData">Actualiser</flux:button>
                    <flux:button variant="primary" color="emerald" icon="receipt-percent" :href="route('facturation.index')" wire:navigate>
                        Factures clinique
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-[1600px] space-y-8 px-4 py-8 lg:px-8">

        <section>
            <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Indicateurs clés</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                <x-analytics.kpi-card label="Factures période" :value="number_format($k['factures_period'])" tone="blue" icon="receipt-percent" />
                <x-analytics.kpi-card label="Factures aujourd'hui" :value="number_format($k['factures_today'])" icon="document-text" />
                <x-analytics.kpi-card label="Montant brut" :value="$this->money($k['gross_billed'])" suffix=" USD" tone="emerald" icon="currency-dollar" />
                <x-analytics.kpi-card label="Part patient" :value="$this->money($k['patient_share'])" suffix=" USD" icon="user" />
                <x-analytics.kpi-card label="Part assurance" :value="$this->money($k['assurance_share'])" suffix=" USD" tone="cyan" icon="shield-check" />
                <x-analytics.kpi-card label="Encaissé période" :value="$this->money($k['collected_period'])" suffix=" USD" tone="emerald" icon="banknotes" />
                <x-analytics.kpi-card label="Encaissé aujourd'hui" :value="$this->money($k['collected_today'])" suffix=" USD" icon="wallet" />
                <x-analytics.kpi-card label="Encaissé ce mois" :value="$this->money($k['collected_month'])" suffix=" USD" tone="emerald" icon="chart-bar" />
                <x-analytics.kpi-card label="Impayés patient" :value="$this->money($k['unpaid_patient'])" suffix=" USD" tone="amber" icon="exclamation-triangle" />
                <x-analytics.kpi-card label="Impayés total" :value="$this->money($k['unpaid_total'])" suffix=" USD" tone="rose" icon="clock" />
                <x-analytics.kpi-card label="Taux encaissement" :value="$k['collection_rate']" suffix="%" tone="blue" icon="check-badge" />
                <x-analytics.kpi-card label="Factures payées" :value="number_format($k['factures_paid'])" tone="emerald" icon="check-circle" />
                <x-analytics.kpi-card label="Paiements partiels" :value="number_format($k['factures_partial'])" tone="amber" icon="adjustments-horizontal" />
                <x-analytics.kpi-card label="En attente" :value="number_format($k['factures_pending'])" tone="rose" icon="pause-circle" />
                <x-analytics.kpi-card label="Consultations facturées" :value="number_format($k['consultations_billed'])" icon="stethoscope" />
                <x-analytics.kpi-card label="Assurances actives" :value="number_format($k['assurance_partners'])" tone="cyan" icon="building-office-2" />
            </div>
        </section>

        @if (count($m['alerts']) > 0)
            <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($m['alerts'] as $alert)
                    <flux:callout :variant="$alert['level'] === 'danger' ? 'danger' : 'warning'" icon="bell-alert">
                        <flux:callout.heading>{{ $alert['title'] }}</flux:callout.heading>
                        {{ $alert['message'] }}
                    </flux:callout>
                @endforeach
            </section>
        @endif

        <section>
            <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Encaissements et facturation</h2>
            <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <x-analytics.chart-panel title="Évolution des encaissements" :chart="$charts['collections']" class="xl:col-span-2" height="h-72" />
                <x-analytics.chart-panel title="Répartition patient / assurance" :chart="$charts['patient_assurance']" />
                <x-analytics.chart-panel title="Facturation brute journalière" :chart="$charts['billing']" />
                <x-analytics.chart-panel title="Modes de paiement" :chart="$charts['payment_modes']" />
                <x-analytics.chart-panel title="Statut des factures" :chart="$charts['invoice_status']" />
            </div>
        </section>

        <section>
            <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Assurances et catégories</h2>
            <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <x-analytics.chart-panel title="Part assurance par organisme" :chart="$charts['by_assurance']" class="xl:col-span-2" height="h-72" />
                <x-analytics.chart-panel title="Montants par catégorie" :chart="$charts['by_category']" />
            </div>

            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Classement des assurances</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-900/70">
                            <tr>
                                <th class="px-5 py-3">Assurance</th>
                                <th class="px-5 py-3">Catégorie</th>
                                <th class="px-5 py-3">Patients</th>
                                <th class="px-5 py-3">Consultations</th>
                                <th class="px-5 py-3 text-right">Brut</th>
                                <th class="px-5 py-3 text-right">Part assurance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($m['assurances']['ranking'] as $assurance)
                                <tr class="border-t border-slate-100 dark:border-slate-800">
                                    <td class="px-5 py-3 font-semibold text-slate-900 dark:text-white">{{ $assurance['name'] }}</td>
                                    <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ $assurance['categorie'] }} ({{ number_format($assurance['coverage'], 0) }}%)</td>
                                    <td class="px-5 py-3">{{ number_format($assurance['patients']) }}</td>
                                    <td class="px-5 py-3">{{ number_format($assurance['consultations']) }}</td>
                                    <td class="px-5 py-3 text-right font-semibold">{{ $this->money($assurance['gross']) }} $</td>
                                    <td class="px-5 py-3 text-right font-semibold text-emerald-700 dark:text-emerald-300">{{ $this->money($assurance['assurance_amount']) }} $</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-500">Aucune prestation assurance sur la période.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <div>
                <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Recettes par département</h2>
                <x-analytics.chart-panel title="Encaissements par département" :chart="$charts['by_department']" height="h-80" />
            </div>
            <div>
                <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Caisse</h2>
                <div class="mb-4 grid gap-4 sm:grid-cols-3">
                    <x-analytics.kpi-card label="Entrées caisse" :value="$this->money($m['cash']['cash_in'])" suffix=" USD" tone="emerald" />
                    <x-analytics.kpi-card label="Sorties caisse" :value="$this->money($m['cash']['cash_out'])" suffix=" USD" tone="rose" />
                    <x-analytics.kpi-card label="Solde net" :value="$this->money($m['cash']['net'])" suffix=" USD" tone="blue" />
                </div>
                <x-analytics.chart-panel title="Entrées de caisse journalières" :chart="$charts['cash_trend']" height="h-64" />
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Dernières factures</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-900/70">
                            <tr>
                                <th class="px-5 py-3">Réf.</th>
                                <th class="px-5 py-3">Patient</th>
                                <th class="px-5 py-3">Assurance</th>
                                <th class="px-5 py-3 text-right">Total</th>
                                <th class="px-5 py-3">État</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($m['invoices']['recent'] as $invoice)
                                <tr class="border-t border-slate-100 dark:border-slate-800">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('facturation.show', $invoice['id']) }}" wire:navigate class="font-mono text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-300">
                                            {{ $invoice['reference'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3">{{ $invoice['patient'] }}</td>
                                    <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ $invoice['assurance'] }}</td>
                                    <td class="px-5 py-3 text-right font-semibold">{{ $this->money($invoice['total']) }} $</td>
                                    <td class="px-5 py-3">{{ $invoice['status'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-slate-500">Aucune facture sur la période.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Principaux impayés</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-900/70">
                            <tr>
                                <th class="px-5 py-3">Réf.</th>
                                <th class="px-5 py-3">Patient</th>
                                <th class="px-5 py-3 text-right">Reste dû</th>
                                <th class="px-5 py-3">État</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($m['invoices']['top_unpaid'] as $invoice)
                                <tr class="border-t border-slate-100 dark:border-slate-800">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('facturation.show', $invoice['id']) }}" wire:navigate class="font-mono text-xs font-semibold text-amber-700 hover:underline dark:text-amber-300">
                                            {{ $invoice['reference'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3">{{ $invoice['patient'] }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-amber-700 dark:text-amber-300">{{ $this->money($invoice['due']) }} $</td>
                                    <td class="px-5 py-3">{{ $invoice['status'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-slate-500">Aucun impayé enregistré.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Derniers paiements encaissés</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-900/70">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Patient</th>
                            <th class="px-5 py-3">Mode</th>
                            <th class="px-5 py-3">Agent</th>
                            <th class="px-5 py-3 text-right">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($m['collections']['recent_payments'] as $payment)
                            <tr class="border-t border-slate-100 dark:border-slate-800">
                                <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ $payment['paid_at'] }}</td>
                                <td class="px-5 py-3">{{ $payment['patient'] }}</td>
                                <td class="px-5 py-3">{{ $payment['mode'] }}</td>
                                <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ $payment['agent'] }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-emerald-700 dark:text-emerald-300">{{ $this->money($payment['amount']) }} $</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-slate-500">Aucun paiement sur la période.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-sky-50 p-6 dark:border-emerald-500/30 dark:from-emerald-500/10 dark:via-slate-900 dark:to-sky-500/10">
            <div class="flex items-center gap-3">
                <div class="rounded-2xl bg-emerald-600 p-3 text-white">
                    <flux:icon.chart-bar class="size-6" />
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Synthèse comptable</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ $m['insights']['summary'] }}</p>
                </div>
            </div>
            <ul class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach ($m['insights']['highlights'] as $highlight)
                    <li class="rounded-2xl border border-white/60 bg-white/80 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-300">
                        {{ $highlight }}
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</div>

@script
<script>
    const apexCdn = @js($apexCdn);

    const loadApexCharts = (callback) => {
        if (typeof ApexCharts !== 'undefined') {
            callback();
            return;
        }

        const existing = document.querySelector('script[data-apexcharts]');

        if (existing) {
            existing.addEventListener('load', callback, { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = apexCdn;
        script.dataset.apexcharts = '1';
        script.async = true;
        script.onload = () => callback();
        document.head.appendChild(script);
    };

    const renderComptabiliteCharts = () => {
        const configs = @js($this->chartOptions);

        Object.entries(configs).forEach(([id, options]) => {
            const element = document.getElementById(id);

            if (!element) {
                return;
            }

            element.replaceChildren();
            new ApexCharts(element, options).render();
        });
    };

    loadApexCharts(renderComptabiliteCharts);
</script>
@endscript
