<?php

namespace App\Services;

use ArielMejiaDev\LarapexCharts\LarapexChart;

class AnalyticsChartBuilder
{
    private const HEIGHT = 280;

    /**
     * @return array<string, LarapexChart>
     */
    public function fromMetrics(array $metrics): array
    {
        $patients = $metrics['patients'];
        $financial = $metrics['financial'];
        $medical = $metrics['medical'];
        $staff = $metrics['staff'];
        $pharmacy = $metrics['pharmacy'];
        $laboratory = $metrics['laboratory'];

        return [
            'admissions' => $this->line(
                'Admissions',
                $patients['admissions_trend']['labels'],
                $patients['admissions_trend']['values'],
            ),
            'gender' => $this->donut(
                $patients['gender']['labels'],
                $patients['gender']['values'],
            ),
            'age' => $this->bar(
                'Patients',
                $patients['age_brackets']['labels'],
                $patients['age_brackets']['values'],
            ),
            'dept_patients' => $this->bar(
                'Consultations',
                $this->pluck($patients['by_department'], 'label'),
                $this->pluck($patients['by_department'], 'value'),
            ),
            'revenue' => $this->line(
                'Recettes',
                $financial['revenue_trend']['labels'],
                $financial['revenue_trend']['values'],
                LarapexChart::COLOR_MINT_GREEN,
            ),
            'expenses' => $this->line(
                'Dépenses',
                $financial['expense_trend']['labels'],
                $financial['expense_trend']['values'],
                LarapexChart::COLOR_CORAL_RED,
            ),
            'profit' => $this->line(
                'Bénéfice',
                $financial['profit_trend']['labels'],
                $financial['profit_trend']['values'],
                LarapexChart::COLOR_OCEAN_BLUE,
            ),
            'payment_modes' => $this->donut(
                $financial['payment_modes']['labels'],
                $financial['payment_modes']['values'],
            ),
            'revenue_streams' => $this->bar(
                'USD',
                $financial['revenue_streams']['labels'],
                $financial['revenue_streams']['values'],
            ),
            'forecast' => $this->area(
                'Projection',
                $financial['forecast']['labels'],
                $financial['forecast']['values'],
            ),
            'specialty' => $this->bar(
                'Consultations',
                $this->pluck($medical['consultations_by_specialty'], 'label'),
                $this->pluck($medical['consultations_by_specialty'], 'value'),
            ),
            'doctors' => $this->bar(
                'Actes',
                $this->pluck($staff['doctor_performance'], 'label'),
                $this->pluck($staff['doctor_performance'], 'value'),
            ),
            'staff_roles' => $this->donut(
                $staff['by_role']['labels'],
                $staff['by_role']['values'],
            ),
            'pharmacy_top' => $this->bar(
                'Quantité',
                $this->pluck($pharmacy['top_sold'], 'label'),
                $this->pluck($pharmacy['top_sold'], 'value'),
            ),
            'lab_top' => $this->bar(
                'Examens',
                $this->pluck($laboratory['top_exams'], 'label'),
                $this->pluck($laboratory['top_exams'], 'value'),
            ),
        ];
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

    private function area(string $seriesName, array $labels, array $values): LarapexChart
    {
        return (new LarapexChart)->areaChart()
            ->setTitle('')
            ->setHeight(self::HEIGHT)
            ->setXAxis($this->labels($labels))
            ->setDataset([['name' => $seriesName, 'data' => $this->values($values)]])
            ->setStroke(2, [LarapexChart::COLOR_AMETHYST_PURPLE], 'smooth')
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
     * @return array<int, string>
     */
    private function pluck(mixed $rows, string $key): array
    {
        return collect($this->rowsToArray($rows))
            ->pluck($key)
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rowsToArray(mixed $rows): array
    {
        if ($rows instanceof Collection) {
            return $rows->map(fn ($row) => $this->rowToArray($row))->values()->all();
        }

        if (! is_iterable($rows)) {
            return [];
        }

        $normalized = [];

        foreach ($rows as $row) {
            $normalized[] = $this->rowToArray($row);
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function rowToArray(mixed $row): array
    {
        if (is_array($row)) {
            return $row;
        }

        if ($row instanceof \stdClass) {
            return (array) $row;
        }

        if (is_object($row)) {
            return get_object_vars($row);
        }

        return ['value' => $row];
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
