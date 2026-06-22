<?php

namespace App\Services;

use ArielMejiaDev\LarapexCharts\LarapexChart;
use Illuminate\Support\Collection;

class ComptabiliteChartBuilder
{
    private const HEIGHT = 280;

    /**
     * @return array<string, LarapexChart>
     */
    public function fromMetrics(array $metrics): array
    {
        $billing = $metrics['billing'];
        $collections = $metrics['collections'];
        $assurances = $metrics['assurances'];
        $cash = $metrics['cash'];
        $invoices = $metrics['invoices'];

        return [
            'collections' => $this->line(
                'Encaissements',
                $collections['collection_trend']['labels'],
                $collections['collection_trend']['values'],
                LarapexChart::COLOR_MINT_GREEN,
            ),
            'billing' => $this->line(
                'Facturation brute',
                $collections['billing_trend']['labels'],
                $collections['billing_trend']['values'],
                LarapexChart::COLOR_OCEAN_BLUE,
            ),
            'payment_modes' => $this->donut(
                $collections['payment_modes']['labels'],
                $collections['payment_modes']['values'],
            ),
            'invoice_status' => $this->donut(
                $invoices['status']['labels'],
                $invoices['status']['values'],
            ),
            'patient_assurance' => $this->donut(
                ['Part patient', 'Part assurance'],
                [$billing['patient'], $billing['assurance']],
            ),
            'by_department' => $this->bar(
                'USD',
                $this->pluck($collections['by_department'], 'label'),
                $this->pluck($collections['by_department'], 'value'),
            ),
            'by_assurance' => $this->bar(
                'USD',
                $assurances['by_assurance']['labels'],
                $assurances['by_assurance']['values'],
            ),
            'by_category' => $this->bar(
                'USD',
                $assurances['by_category']['labels'],
                $assurances['by_category']['values'],
            ),
            'cash_trend' => $this->area(
                'Entrées caisse',
                $cash['trend']['labels'],
                $cash['trend']['values'],
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
