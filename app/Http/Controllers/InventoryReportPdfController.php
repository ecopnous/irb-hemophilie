<?php

namespace App\Http\Controllers;

use App\Models\facturation\InventoryAsset;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class InventoryReportPdfController extends Controller
{
    public function __invoke(): Response
    {
        $assets = InventoryAsset::query()
            ->with(['location.departement', 'location.service', 'category'])
            ->where('hopital_id', current_hopital_id())
            ->orderBy('inventory_number')
            ->get();

        $stats = [
            'count' => $assets->count(),
            'acquisition' => (float) $assets->sum(fn (InventoryAsset $asset) => (float) $asset->acquisition_cost),
            'depreciation' => (float) $assets->sum(fn (InventoryAsset $asset) => $asset->accumulatedDepreciationAmount()),
            'net' => (float) $assets->sum(fn (InventoryAsset $asset) => $asset->netBookValue()),
        ];

        $html = View::make('pdf.inventory-report', [
            'assets' => $assets,
            'stats' => $stats,
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 96);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $fileName = 'rapport-inventaire-' . now()->format('Ymd-His') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }
}
