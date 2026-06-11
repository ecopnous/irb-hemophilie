<?php

use App\Services\AnalyticsChartBuilder;
use App\Services\AnalyticsMetricsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Analytics hospitalier')] class extends Component {
    public string $period = 'month';
    public ?string $date_start = null;
    public ?string $date_end = null;

    public function refreshData(): void
    {
        app(AnalyticsMetricsService::class)->forgetDashboardCache(
            $this->period,
            $this->date_start,
            $this->date_end,
        );

        unset($this->metrics, $this->charts, $this->chartOptions);
    }

    #[Computed]
    public function metrics(): array
    {
        return app(AnalyticsMetricsService::class)->dashboard(
            $this->period,
            $this->date_start,
            $this->date_end,
        );
    }

    #[Computed]
    public function charts(): array
    {
        return app(AnalyticsChartBuilder::class)->fromMetrics($this->metrics);
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

    public function exportQuery(): string
    {
        return http_build_query([
            'period' => $this->period,
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
        ]);
    }
};
?>

@php($k = $this->metrics['kpis'])
@php($m = $this->metrics)
@php($charts = $this->charts)
@php($apexCdn = \ArielMejiaDev\LarapexCharts\LarapexChart::cdn().'/dist/apexcharts.min.js')

<div class="min-h-screen bg-[#eef2f7] dark:bg-slate-950" wire:key="analytics-{{ $period }}-{{ $date_start }}-{{ $date_end }}">

    {{-- En-tête --}}
    <div class="border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 sticky top-0 z-20">
        <div class="mx-auto max-w-[1600px] px-4 py-5 lg:px-8">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <x-breadcrumbs :items="[
                        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                        ['label' => 'Analytics', 'icon' => 'chart-bar'],
                    ]" />
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                        Intelligence décisionnelle — {{ current_hopital_nom() }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ now()->translatedFormat('l d F Y') }} · {{ $m['periodLabel'] }}
                    </p>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex flex-wrap gap-2">
                        @foreach (['today' => "Aujourd'hui", 'week' => 'Semaine', 'month' => 'Mois', 'year' => 'Année', 'custom' => 'Personnalisée'] as $key => $label)
                            <button type="button" wire:click="$set('period', '{{ $key }}')"
                                class="rounded-xl px-3 py-2 text-xs font-bold transition {{ $period === $key ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    @if ($period === 'custom')
                        <input type="date" wire:model.live="date_start" class="rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                        <input type="date" wire:model.live="date_end" class="rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                    @endif

                    <flux:button variant="ghost" icon="arrow-path" wire:click="refreshData">Actualiser</flux:button>
                    <flux:button variant="ghost" icon="document-arrow-down" :href="route('analytics.export.excel') . '?' . $this->exportQuery()">Excel</flux:button>
                    <flux:button variant="primary" color="indigo" icon="document-text" :href="route('analytics.export.pdf') . '?' . $this->exportQuery()">PDF</flux:button>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-[1600px] space-y-8 px-4 py-8 lg:px-8">

        {{-- KPI principaux --}}
        <section>
            <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Indicateurs clés</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                <x-analytics.kpi-card label="Patients aujourd'hui" :value="number_format($k['patients_today'])" tone="blue" icon="user-plus" />
                <x-analytics.kpi-card label="Patients ce mois" :value="number_format($k['patients_month'])" icon="users" />
                <x-analytics.kpi-card label="Hospitalisés" :value="number_format($k['hospitalized'])" tone="cyan" icon="home-modern" />
                <x-analytics.kpi-card label="Sorties" :value="number_format($k['discharged'])" icon="arrow-right-start-on-rectangle" />
                <x-analytics.kpi-card label="Consultations" :value="number_format($k['consultations_total'])" tone="emerald" icon="stethoscope" />
                <x-analytics.kpi-card label="Chirurgies" :value="number_format($k['surgeries'])" icon="scissors" />
                <x-analytics.kpi-card label="Urgences" :value="number_format($k['emergencies'])" tone="rose" icon="bolt" />
                <x-analytics.kpi-card label="Rendez-vous" :value="number_format($k['appointments'])" icon="calendar-days" />
                <x-analytics.kpi-card label="Occupation lits" :value="$k['bed_occupancy_rate']" suffix="%" tone="amber" icon="chart-bar" />
                <x-analytics.kpi-card label="Médecins actifs" :value="number_format($k['active_doctors'])" icon="user-circle" />
                <x-analytics.kpi-card label="Infirmiers" :value="number_format($k['active_nurses'])" icon="heart" />
                <x-analytics.kpi-card label="Employés" :value="number_format($k['active_employees'])" icon="building-office" />
                <x-analytics.kpi-card label="Recettes jour" :value="number_format($k['revenue_today'], 0, ',', ' ')" suffix=" USD" tone="emerald" icon="banknotes" />
                <x-analytics.kpi-card label="Recettes mois" :value="number_format($k['revenue_month'], 0, ',', ' ')" suffix=" USD" tone="emerald" icon="currency-dollar" />
                <x-analytics.kpi-card label="Dépenses mois" :value="number_format($k['expenses_month'], 0, ',', ' ')" suffix=" USD" tone="rose" icon="arrow-trending-down" />
                <x-analytics.kpi-card label="Bénéfice net" :value="number_format($k['net_profit_month'], 0, ',', ' ')" suffix=" USD" tone="blue" icon="chart-pie" />
                <x-analytics.kpi-card label="Impayés" :value="number_format($k['unpaid_invoices'], 0, ',', ' ')" suffix=" USD" tone="amber" icon="exclamation-triangle" />
                <x-analytics.kpi-card label="Encaissé" :value="number_format($k['collected_period'], 0, ',', ' ')" suffix=" USD" tone="cyan" icon="check-badge" />
            </div>
        </section>

        {{-- Alertes --}}
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

        {{-- Patients --}}
        <section>
            <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Analyse des patients</h2>
            <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <x-analytics.chart-panel title="Évolution des admissions" :chart="$charts['admissions']" class="xl:col-span-2" height="h-72" />
                <x-analytics.chart-panel title="Répartition par genre" :chart="$charts['gender']" />
                <x-analytics.chart-panel title="Tranches d'âge" :chart="$charts['age']" />
                <x-analytics.chart-panel title="Patients par service" :chart="$charts['dept_patients']" class="xl:col-span-2" />
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <x-analytics.kpi-card label="Taux de réadmission" :value="$m['patients']['readmission_rate']" suffix="%" tone="amber" />
                <x-analytics.kpi-card label="Durée moy. hospitalisation" :value="$m['patients']['avg_hospitalization_days']" suffix=" j" tone="cyan" />
                <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Top diagnostics</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        @forelse ($m['patients']['top_diagnostics'] as $diag)
                            <li class="flex justify-between gap-2"><span class="truncate">{{ $diag['label'] }}</span><strong>{{ $diag['value'] }}</strong></li>
                        @empty
                            <li class="text-slate-400">Aucune donnée</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </section>

        {{-- Financier --}}
        <section>
            <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Analyse financière</h2>
            <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <x-analytics.chart-panel title="Évolution des recettes" :chart="$charts['revenue']" />
                <x-analytics.chart-panel title="Évolution des dépenses" :chart="$charts['expenses']" />
                <x-analytics.chart-panel title="Évolution du bénéfice" :chart="$charts['profit']" />
                <x-analytics.chart-panel title="Modes de paiement" :chart="$charts['payment_modes']" />
                <x-analytics.chart-panel title="Revenus par activité" :chart="$charts['revenue_streams']" />
                <x-analytics.chart-panel title="Prévisions financières" :chart="$charts['forecast']" subtitle="Projection sur 6 mois" />
            </div>
        </section>

        {{-- Médical & Services --}}
        <section class="grid gap-4 xl:grid-cols-2">
            <div>
                <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Analyse médicale</h2>
                <x-analytics.chart-panel title="Consultations par spécialité" :chart="$charts['specialty']" height="h-80" />
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <x-analytics.kpi-card label="Examens labo" :value="number_format($m['medical']['lab_exams'])" tone="cyan" />
                    <x-analytics.kpi-card label="Imagerie" :value="number_format($m['medical']['imaging_exams'])" />
                    <x-analytics.kpi-card label="Tps prise en charge" :value="$m['medical']['avg_care_time_minutes']" suffix=" min" />
                    <x-analytics.kpi-card label="Tps attente" :value="$m['medical']['avg_wait_minutes']" suffix=" min" tone="amber" />
                </div>
            </div>
            <div>
                <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Services hospitaliers</h2>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-xs uppercase text-slate-500"><th class="pb-2">Service</th><th>Patients</th><th>Visites</th></tr></thead>
                        <tbody>
                            @foreach ($m['services']['ranking'] as $service)
                                <tr class="border-t border-slate-100 dark:border-slate-800">
                                    <td class="py-2 font-medium">{{ $service['label'] }}</td>
                                    <td>{{ number_format($service['patients']) }}</td>
                                    <td>{{ number_format($service['visits'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- Personnel, Pharmacie, Labo, Lits --}}
        <section class="grid gap-4 xl:grid-cols-2">
            <x-analytics.chart-panel title="Performance des médecins" :chart="$charts['doctors']" height="h-72" />
            <x-analytics.chart-panel title="Répartition du personnel" :chart="$charts['staff_roles']" height="h-72" />
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <x-analytics.chart-panel title="Médicaments les plus dispensés" :chart="$charts['pharmacy_top']" />
            <div class="space-y-3">
                <x-analytics.kpi-card label="Valeur stock pharmacie" :value="number_format($m['pharmacy']['stock_value'], 0, ',', ' ')" suffix=" USD" />
                <x-analytics.kpi-card label="Stock critique" :value="number_format($m['pharmacy']['critical_stock'])" tone="rose" />
                <x-analytics.kpi-card label="Expiration < 90j" :value="number_format($m['pharmacy']['expiring_soon'])" tone="amber" />
            </div>
            <div class="space-y-3">
                <x-analytics.kpi-card label="Examens labo (top)" :value="number_format($m['laboratory']['results_delivered'])" tone="cyan" />
                <x-analytics.kpi-card label="Revenus laboratoire" :value="number_format($m['laboratory']['revenue'], 0, ',', ' ')" suffix=" USD" />
                <x-analytics.kpi-card label="Taux de retard" :value="$m['laboratory']['delay_rate']" suffix="%" tone="amber" />
            </div>
        </section>

        <section>
            <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Gestion des lits</h2>
            <div class="grid gap-4 sm:grid-cols-4">
                <x-analytics.kpi-card label="Lits totaux" :value="number_format($m['beds']['total'])" />
                <x-analytics.kpi-card label="Occupés" :value="number_format($m['beds']['occupied'])" tone="rose" />
                <x-analytics.kpi-card label="Disponibles" :value="number_format($m['beds']['available'])" tone="emerald" />
                <x-analytics.kpi-card label="Taux occupation" :value="$m['beds']['occupancy_rate']" suffix="%" tone="amber" />
            </div>
        </section>

        {{-- Intelligence décisionnelle --}}
        <section class="rounded-3xl border border-indigo-200 bg-linear-to-br from-indigo-50 via-white to-cyan-50 p-6 dark:border-indigo-500/30 dark:from-indigo-500/10 dark:via-slate-900 dark:to-cyan-500/10">
            <div class="flex items-center gap-3">
                <div class="rounded-2xl bg-indigo-600 p-3 text-white"><flux:icon.sparkles class="size-6" /></div>
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Intelligence décisionnelle</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ $m['insights']['summary'] }}</p>
                </div>
            </div>
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ([
                    'Tendances' => $m['insights']['trends'],
                    'Recommandations' => $m['insights']['recommendations'],
                    'Risques' => $m['insights']['risks'],
                    'Opportunités' => $m['insights']['opportunities'],
                    'Prédictions' => $m['insights']['predictions'],
                    'Anomalies' => $m['insights']['anomalies'],
                ] as $title => $items)
                    <div class="rounded-2xl border border-white/60 bg-white/80 p-4 dark:border-slate-700 dark:bg-slate-900/80">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $title }}</h3>
                        <ul class="mt-2 list-disc space-y-1 pl-4 text-sm text-slate-600 dark:text-slate-300">
                            @forelse ($items as $item)
                                <li>{{ $item }}</li>
                            @empty
                                <li class="list-none pl-0 text-slate-400">Rien à signaler</li>
                            @endforelse
                        </ul>
                    </div>
                @endforeach
            </div>
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

    const renderAnalyticsCharts = () => {
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

    loadApexCharts(renderAnalyticsCharts);
</script>
@endscript
