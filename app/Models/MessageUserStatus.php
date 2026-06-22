<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageUserStatus extends Model
{
    protected $fillable = [
        'clinical_message_id',
        'user_id',
        'read_at',
        'archived_at',
        'deleted_at',
        'starred_at',
        'important_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
            'starred_at' => 'datetime',
            'important_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ClinicalMessage::class, 'clinical_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
