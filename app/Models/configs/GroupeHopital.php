<?php

namespace App\Models\Configs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GroupeHopital extends Model
{
    protected $fillable = [
        'nom',
        'objetif',
        'note',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hopitaux(): BelongsToMany
    {
        return $this->belongsToMany(Hopital::class, 'groupe_hopital_hopital')->withTimestamps();
    }
}
