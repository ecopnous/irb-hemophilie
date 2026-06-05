<?php

namespace App\Models\facturation;

use App\Models\Configs\Acte;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceDocumentItem extends Model
{
    protected $fillable = [
        'finance_document_id',
        'acte_id',
        'line_type',
        'designation',
        'quantity',
        'price_ht',
        'tva',
        'discount',
        'total_ht',
        'total_ttc',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price_ht' => 'decimal:2',
        'tva' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_ht' => 'decimal:2',
        'total_ttc' => 'decimal:2',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'finance_document_id');
    }

    public function acte(): BelongsTo
    {
        return $this->belongsTo(Acte::class);
    }
}
