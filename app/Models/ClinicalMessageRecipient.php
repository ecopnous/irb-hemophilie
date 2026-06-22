<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalMessageRecipient extends Model
{
    protected $fillable = [
        'clinical_message_id',
        'recipient_type',
        'recipient_id',
        'display_name',
        'routing_key',
        'channel',
        'read_at',
        'acknowledged_at',
        'archived_at',
        'deleted_at',
        'starred_at',
        'important_at',
        'delivery_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'acknowledged_at' => 'datetime',
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
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
