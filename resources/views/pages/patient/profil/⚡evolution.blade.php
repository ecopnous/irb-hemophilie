<?php

use App\Models\DossierPatient;
use App\Services\PatientEvolutionChartBuilder;
use App\Services\PatientEvolutionMetricsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Évolution du patient'), Layout('layouts::app.other.profil_medical')] class extends Component {
    public DossierPatient $patient;

    public string $period = 'all';
    public ?string $date_start = null;
    public ?string $date_end = null;
    public string $departement_id = '';
    public string $consultation_type = '';
    public string $user_id = '';
    public string $compare_mode = 'first_last';

    public function mount(int $id): void
    {
        $this->patient = DossierPatient::query()->findOrFail($id);
    }

    public function refreshData(): void
    {
        app(PatientEvolutionMetricsService::class)->forgetCache($this->patient->id, $this->filterPayload());
        unset($this->metrics, $this->charts, $this->chartOptions);
    }

    /**
     * @return array<string, mixed>
     */
    private function filterPayload(): array
    {
        return [
            'period' => $this->period,
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
            'departement_id' => $this->departement_id,
            'consultation_type' => $this->consultation_type,
            'user_id' => $this->user_id,
            'compare_mode' => $this->compare_mode,
        ];
    }

    #[Computed]
    public function metrics(): array
    {
        return app(PatientEvolutionMetricsService::class)->dashboard(
            $this->patient->id,
            $this->filterPayload(),
        );
    }

    #[Computed]
    public function charts(): array
    {
        return app(PatientEvolutionChartBuilder::class)->fromMetrics($this->metrics);
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
};
?>

@php($k = $this->metrics['kpis'])
@php($m = $this->metrics)
@php($charts = $this->charts)
@php($apexCdn = \ArielMejiaDev\LarapexCharts\LarapexChart::cdn().'/dist/apexcharts.min.js')

<div class="min-h-screen bg-[#eef2f7] dark:bg-slate-950" wire:key="evolution-{{ $patient->id }}-{{ $period }}-{{ $departement_id }}-{{ $consultation_type }}-{{ $user_id }}-{{ $compare_mode }}-{{ $date_start }}-{{ $date_end }}">
    <x-patient.patient-profil-header
        :nav="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Dossiers patients', 'link' => 'patient.index', 'icon' => 'folder'],
            ['label' => $patient->nin, 'link' => route('patient.show', $patient->id), 'icon' => 'identification'],
        ]"
        :patient="$patient"
        :current_patient="$patient->id"
    >
        <x-slot name="title">Évolution du patient</x-slot>
        <x-slot name="subtitle">Suivi longitudinal · {{ $m['patient']['full_name'] }} · {{ $m['periodLabel'] }}</x-slot>
    </x-patient.patient-profil-header>

    {{-- Filtres --}}
    <div class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
        <div class="mx-auto max-w-[1600px] px-4 py-4 lg:px-8">
            <div class="grid gap-3 lg:grid-cols-12 lg:items-end">
                <div class="lg:col-span-5">
                    <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Période</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['all' => 'Tout', '3months' => '3 mois', '6months' => '6 mois', 'year' => '1 an', '2years' => '2 ans', 'custom' => 'Personnalisée'] as $key => $label)
                            <button type="button" wire:click="$set('period', '{{ $key }}')"
                                class="rounded-xl px-3 py-2 text-xs font-bold transition {{ $period === $key ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @if ($period === 'custom')
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Du</label>
                        <input type="date" wire:model.live="date_start" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Au</label>
                        <input type="date" wire:model.live="date_end" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                    </div>
                @endif

                <div class="{{ $period === 'custom' ? 'lg:col-span-3' : 'lg:col-span-7' }} grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Service</label>
                        <select wire:model.live="departement_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                            <option value="">Tous les services</option>
                            @foreach ($m['filters']['departements'] as $option)
                                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Type</label>
                        <select wire:model.live="consultation_type" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                            <option value="">Tous les types</option>
                            <option value="consultation">Consultation</option>
                            <option value="depistage">Dépistage / Urgence</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Médecin</label>
                        <select wire:model.live="user_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                            <option value="">Tous les médecins</option>
                            @foreach ($m['filters']['doctors'] as $option)
                                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Comparer</label>
                        <select wire:model.live="compare_mode" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                            <option value="first_last">1ère vs dernière visite</option>
                            <option value="last_two">2 dernières visites</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2 lg:col-span-12 lg:justify-end">
                    <flux:button variant="ghost" icon="arrow-path" wire:click="refreshData">Actualiser</flux:button>
                    <flux:button variant="ghost" icon="newspaper" :href="route('consultation.historique', $patient->id)" wire:navigate>Historique détaillé</flux:button>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-[1600px] space-y-8 px-4 py-8 lg:px-8">
        @if ($k['total_consultations'] === 0)
            <flux:callout variant="warning" icon="information-circle">
                <flux:callout.heading>Aucune consultation</flux:callout.heading>
                Aucune consultation ne correspond aux critères sélectionnés pour ce patient.
            </flux:callout>
        @else
            {{-- Insights --}}
            <section class="rounded-3xl border border-indigo-200 bg-linear-to-br from-indigo-50 via-white to-cyan-50 p-6 dark:border-indigo-500/30 dark:from-indigo-500/10 dark:via-slate-900 dark:to-cyan-500/10">
                <p class="text-sm text-slate-600 dark:text-slate-300">{{ $m['insights']['summary'] }}</p>
                @if (count($m['insights']['alerts']) > 0)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($m['insights']['alerts'] as $alert)
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-500/20 dark:text-amber-200">{{ $alert }}</span>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- KPIs --}}
            <section>
                <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Indicateurs de suivi</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                    <x-analytics.kpi-card label="Consultations" :value="number_format($k['total_consultations'])" tone="blue" icon="stethoscope" />
                    <x-analytics.kpi-card label="1ère visite" :value="$k['first_visit'] ?? '-'" icon="calendar" />
                    <x-analytics.kpi-card label="Dernière visite" :value="$k['last_visit'] ?? '-'" tone="cyan" icon="calendar-days" />
                    <x-analytics.kpi-card label="Jours depuis visite" :value="$k['days_since_last_visit'] ?? '-'" tone="amber" icon="clock" />
                    <x-analytics.kpi-card label="Services consultés" :value="number_format($k['departments_count'])" icon="building-office-2" />
                    <x-analytics.kpi-card label="Médecins" :value="number_format($k['doctors_count'])" icon="user-circle" />
                    <x-analytics.kpi-card label="Examens labo" :value="number_format($k['lab_exams'])" tone="cyan" icon="beaker" />
                    <x-analytics.kpi-card label="Imagerie" :value="number_format($k['imaging_exams'])" icon="photo" />
                    <x-analytics.kpi-card label="Prescriptions" :value="number_format($k['prescriptions'])" tone="emerald" icon="clipboard-document-list" />
                    <x-analytics.kpi-card label="Hospitalisations" :value="number_format($k['hospitalizations'])" tone="rose" icon="home-modern" />
                    <x-analytics.kpi-card label="Poids actuel" :value="$k['latest_weight'] ?? '-'" suffix=" kg" tone="blue" icon="scale" />
                    <x-analytics.kpi-card label="Tension SYS" :value="$k['latest_systolic'] ?? '-'" suffix=" mmHg" tone="rose" icon="heart" />
                </div>
            </section>

            {{-- Activité --}}
            <section>
                <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Activité & consultations</h2>
                <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                    <x-analytics.chart-panel title="Fréquence des visites" :chart="$charts['visits_trend']" class="xl:col-span-2" height="h-72" />
                    <x-analytics.chart-panel title="Répartition par type" :chart="$charts['by_type']" />
                    <x-analytics.chart-panel title="Consultations par service" :chart="$charts['by_department']" class="xl:col-span-2" />
                </div>
            </section>

            {{-- Signes vitaux --}}
            <section>
                <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Évolution des constantes vitales</h2>
                <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                    <x-analytics.chart-panel title="Poids" :chart="$charts['weight']" />
                    <x-analytics.chart-panel title="Tension artérielle" :chart="$charts['blood_pressure']" />
                    <x-analytics.chart-panel title="Température" :chart="$charts['temperature']" />
                    <x-analytics.chart-panel title="Fréquence cardiaque" :chart="$charts['heart_rate']" />
                    <x-analytics.chart-panel title="Glycémie" :chart="$charts['glycemia']" />
                    <x-analytics.chart-panel title="Saturation O₂" :chart="$charts['oxygen']" />
                </div>
            </section>

            {{-- Comparaison --}}
            @if (! empty($m['comparison']['rows']))
                <section class="grid gap-4 xl:grid-cols-2">
                    <x-analytics.chart-panel title="Comparaison des constantes — {{ $m['comparison']['label'] }}" :chart="$charts['comparison'] ?? null" height="h-80" />
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tableau comparatif</h3>
                        <p class="mt-1 text-xs text-slate-500">{{ $m['comparison']['first_date'] }} → {{ $m['comparison']['last_date'] }}</p>
                        <table class="mt-4 w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-slate-500">
                                    <th class="pb-2">Constante</th>
                                    <th>Début</th>
                                    <th>Fin</th>
                                    <th>Δ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($m['comparison']['rows'] as $row)
                                    <tr class="border-t border-slate-100 dark:border-slate-800">
                                        <td class="py-2 font-medium">{{ $row['metric'] }}</td>
                                        <td>{{ $row['first'] ?? '—' }}</td>
                                        <td>{{ $row['last'] ?? '—' }}</td>
                                        <td class="{{ ($row['delta'] ?? 0) > 0 ? 'text-rose-600' : (($row['delta'] ?? 0) < 0 ? 'text-emerald-600' : '') }}">
                                            @if ($row['delta'] !== null)
                                                {{ $row['delta'] > 0 ? '+' : '' }}{{ $row['delta'] }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            {{-- Clinique & examens --}}
            <section class="grid gap-4 xl:grid-cols-2">
                <div>
                    <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Analyse clinique</h2>
                    <x-analytics.chart-panel title="Diagnostics les plus fréquents" :chart="$charts['diagnostics']" height="h-72" />
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <x-analytics.kpi-card label="Consultations clôturées" :value="number_format($m['clinical']['closed_consultations'])" tone="emerald" />
                        <x-analytics.kpi-card label="En cours" :value="number_format($m['clinical']['open_consultations'])" tone="amber" />
                    </div>
                </div>
                <div>
                    <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Examens & actes</h2>
                    <x-analytics.chart-panel title="Examens laboratoire dans le temps" :chart="$charts['lab_trend']" height="h-72" />
                    <x-analytics.chart-panel title="Actes médicaux les plus réalisés" :chart="$charts['top_actes']" class="mt-4" />
                </div>
            </section>

            {{-- Timeline --}}
            <section>
                <h2 class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-slate-500">Dernières consultations</h2>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-800/50">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Référence</th>
                                <th class="px-4 py-3">Service</th>
                                <th class="px-4 py-3">Médecin</th>
                                <th class="px-4 py-3">Diagnostic</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($m['timeline'] as $visit)
                                <tr class="border-t border-slate-100 dark:border-slate-800">
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $visit['date'] }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $visit['reference'] }}</td>
                                    <td class="px-4 py-3">{{ $visit['department'] }}</td>
                                    <td class="px-4 py-3">{{ $visit['doctor'] }}</td>
                                    <td class="px-4 py-3 max-w-xs truncate">{{ $visit['diagnostic'] }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <flux:button size="xs" variant="ghost" :href="route('consultation.show', $visit['id'])" wire:navigate>Voir</flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
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

    const renderEvolutionCharts = () => {
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

    loadApexCharts(renderEvolutionCharts);
</script>
@endscript
