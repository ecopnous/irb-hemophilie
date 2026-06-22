<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLabel extends Model
{
    protected $fillable = [
        'clinical_message_id',
        'user_id',
        'name',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(ClinicalMessage::class, 'clinical_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
