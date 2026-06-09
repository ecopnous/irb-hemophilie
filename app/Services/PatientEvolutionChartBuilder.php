<?php

namespace App\Services;

use ArielMejiaDev\LarapexCharts\LarapexChart;

class PatientEvolutionChartBuilder
{
    private const HEIGHT = 280;

    /**
     * @return array<string, LarapexChart>
     */
    public function fromMetrics(array $metrics): array
    {
        $activity = $metrics['activity'];
        $vitals = $metrics['vitals'];
        $clinical = $metrics['clinical'];
        $exams = $metrics['exams'];
        $comparison = $metrics['comparison'];

        $charts = [
            'visits_trend' => $this->bar(
                'Consultations',
                $activity['monthly_trend']['labels'],
                $activity['monthly_trend']['values'],
            ),
            'by_department' => $this->bar(
                'Visites',
                $this->pluck($activity['by_department'], 'label'),
                $this->pluck($activity['by_department'], 'value'),
            ),
            'by_type' => $this->donut(
                $activity['by_type']['labels'],
                $activity['by_type']['values'],
            ),
            'weight' => $this->line('Poids (kg)', $vitals['labels'], $this->numericOnly($vitals['weight']), LarapexChart::COLOR_OCEAN_BLUE),
            'blood_pressure' => $this->multiLine(
                $vitals['labels'],
                [
                    ['name' => 'Systolique', 'data' => $this->numericOnly($vitals['systolic']), 'color' => LarapexChart::COLOR_CORAL_RED],
                    ['name' => 'Diastolique', 'data' => $this->numericOnly($vitals['diastolic']), 'color' => LarapexChart::COLOR_OCEAN_BLUE],
                ],
            ),
            'temperature' => $this->line('°C', $vitals['labels'], $this->numericOnly($vitals['temperature']), LarapexChart::COLOR_AMBER_ORANGE),
            'heart_rate' => $this->line('bpm', $vitals['labels'], $this->numericOnly($vitals['heart_rate']), LarapexChart::COLOR_CORAL_RED),
            'glycemia' => $this->line('Glycémie', $vitals['labels'], $this->numericOnly($vitals['glycemia']), LarapexChart::COLOR_AMETHYST_PURPLE),
            'oxygen' => $this->line('SpO2 %', $vitals['labels'], $this->numericOnly($vitals['oxygen_saturation']), LarapexChart::COLOR_MINT_GREEN),
            'diagnostics' => $this->bar(
                'Occurrences',
                $this->pluck($clinical['top_diagnostics'], 'label'),
                $this->pluck($clinical['top_diagnostics'], 'value'),
            ),
            'lab_trend' => $this->area(
                'Examens labo',
                $exams['lab_trend']['labels'],
                $exams['lab_trend']['values'],
            ),
            'top_actes' => $this->bar(
                'Actes',
                $this->pluck($exams['top_actes'], 'label'),
                $this->pluck($exams['top_actes'], 'value'),
            ),
        ];

        if (! empty($comparison['rows'])) {
            $charts['comparison'] = $this->groupedBar(
                'Comparaison',
                collect($comparison['rows'])->pluck('metric')->all(),
                [
                    ['name' => $comparison['first_date'], 'data' => collect($comparison['rows'])->pluck('first')->map(fn ($v) => (float) ($v ?? 0))->all()],
                    ['name' => $comparison['last_date'], 'data' => collect($comparison['rows'])->pluck('last')->map(fn ($v) => (float) ($v ?? 0))->all()],
                ],
            );
        }

        return $charts;
    }

    private function line(string $seriesName, array $labels, array $values, string $color = LarapexChart::COLOR_OCEAN_BLUE): LarapexChart
    {
        return (new LarapexChart)->lineChart()
            ->setTitle('')
            ->setHeight(self::HEIGHT)
            ->setXAxis($this->labels($labels))
            ->setDataset([['name' => $seriesName, 'data' => $this->values($values)]])
            ->setStroke(3, [$color], 'smooth')
            ->setMarkers([$color])
            ->setGrid('#e2e8f0', 0.45, 4)
            ->setToolbar(false)
            ->setShowLegend(false);
    }

    /**
     * @param  array<int, array{name: string, data: array<int, float>, color: string}>  $series
     */
    private function multiLine(array $labels, array $series): LarapexChart
    {
        $chart = (new LarapexChart)->lineChart()
            ->setTitle('')
            ->setHeight(self::HEIGHT)
            ->setXAxis($this->labels($labels))
            ->setGrid('#e2e8f0', 0.45, 4)
            ->setToolbar(false)
            ->setShowLegend(true);

        foreach ($series as $item) {
            $chart->addData($this->values($item['data']), $item['name']);
        }

        return $chart->setStroke(2, array_column($series, 'color'), 'smooth');
    }

    private function area(string $seriesName, array $labels, array $values): LarapexChart
    {
        return (new LarapexChart)->areaChart()
            ->setTitle('')
            ->setHeight(self::HEIGHT)
            ->setXAxis($this->labels($labels))
            ->setDataset([['name' => $seriesName, 'data' => $this->values($values)]])
            ->setStroke(2, [LarapexChart::COLOR_CYAN_SKY], 'smooth')
            ->setGrid('#e2e8f0', 0.45, 4)
            ->setToolbar(false)
            ->setShowLegend(false);
    }

    private function bar(string $seriesName, array $labels, array $values): LarapexChart
    {
        return (new LarapexChart)->barChart()
            ->setTitle('')
            ->setHeight(self::HEIGHT)
            ->setXAxis($this->labels($labels))
            ->setDataset([['name' => $seriesName, 'data' => $this->values($values)]])
            ->setGrid('#e2e8f0', 0.45, 4)
            ->setToolbar(false)
            ->setShowLegend(false)
            ->setDataLabels(false);
    }

    /**
     * @param  array<int, array{name: string, data: array<int, float>}>  $series
     */
    private function groupedBar(string $title, array $labels, array $series): LarapexChart
    {
        $chart = (new LarapexChart)->barChart()
            ->setTitle('')
            ->setHeight(320)
            ->setXAxis($this->labels($labels))
            ->setGrid('#e2e8f0', 0.45, 4)
            ->setToolbar(false)
            ->setShowLegend(true)
            ->setDataLabels(false);

        foreach ($series as $item) {
            $chart->addData($this->values($item['data']), $item['name']);
        }

        return $chart;
    }

    private function donut(array $labels, array $values): LarapexChart
    {
        return (new LarapexChart)->donutChart()
            ->setTitle('')
            ->setHeight(self::HEIGHT)
            ->setLabels($this->labels($labels))
            ->setDataset($this->values($values))
            ->setToolbar(false)
            ->setDataLabels(true);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, string>
     */
    private function pluck(array $rows, string $key): array
    {
        return array_map(fn ($row) => (string) ($row[$key] ?? ''), $rows);
    }

    /**
     * @param  array<int, float|null>  $values
     * @return array<int, float>
     */
    private function numericOnly(array $values): array
    {
        $filtered = array_values(array_filter($values, fn ($v) => $v !== null));

        return $filtered === [] ? [0] : $filtered;
    }

    /**
     * @return array<int, string>
     */
    private function labels(array $labels): array
    {
        if ($labels === []) {
            return ['Aucune donnée'];
        }

        return array_map(fn ($label) => (string) $label, array_values($labels));
    }

    /**
     * @return array<int, float>
     */
    private function values(array $values): array
    {
        if ($values === []) {
            return [0];
        }

        return array_map(
            fn ($value) => is_numeric($value) ? (float) $value : 0.0,
            array_values($values),
        );
    }
}
