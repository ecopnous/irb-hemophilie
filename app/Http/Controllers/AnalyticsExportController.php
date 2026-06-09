<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsMetricsService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsExportController extends Controller
{
    public function excel(Request $request)
    {
        $metrics = $this->metrics($request);

        return Excel::download(
            new \App\Exports\AnalyticsSummaryExport($metrics),
            'analytics-' . now()->format('Y-m-d') . '.xlsx',
        );
    }

    public function pdf(Request $request): Response
    {
        $metrics = $this->metrics($request);

        $html = View::make('pdf.analytics-summary', [
            'metrics' => $metrics,
            'hopital' => current_hopital_nom(),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->render();

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="analytics-' . now()->format('Y-m-d') . '.pdf"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function metrics(Request $request): array
    {
        return app(AnalyticsMetricsService::class)->dashboard(
            period: $request->string('period', 'month')->toString(),
            customStart: $request->string('date_start')->toString() ?: null,
            customEnd: $request->string('date_end')->toString() ?: null,
        );
    }
}
