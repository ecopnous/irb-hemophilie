<?php

namespace App\Models\facturation;

use App\Models\Configs\Hopital;
use App\Models\Concerns\ScopesByHopital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceClient extends Model
{
    use ScopesByHopital;

    protected $fillable = [
        'hopital_id',
        'name',
        'type',
        'email',
        'phone',
        'address',
        'tax_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function hopital(): BelongsTo
    {
        return $this->belongsTo(Hopital::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FinanceDocument::class);
    }

    public function displayLabel(): string
    {
        $parts = array_filter([
            $this->name,
            $this->phone,
            $this->email,
        ]);

        return implode(' — ', $parts);
    }

    public function typeLabel(): string
    {
        return $this->type === 'institution' ? 'Institution' : 'Particulier';
    }
}
