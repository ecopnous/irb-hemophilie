<?php

namespace App\Models;

use App\Enums\ClinicalMessageCategory;
use App\Enums\ClinicalMessagePriority;
use App\Enums\ClinicalMessageStatus;
use App\Models\Concerns\ScopesByHopital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalMessage extends Model
{
    use ScopesByHopital;

    protected $fillable = [
        'hopital_id',
        'thread_id',
        'dossier_patient_id',
        'consultation_id',
        'parent_id',
        'sender_id',
        'sender_type',
        'message_type',
        'category',
        'priority',
        'subject',
        'body',
        'recipient_summary',
        'status',
        'sent_at',
        'last_activity_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ClinicalMessageCategory::class,
            'priority' => ClinicalMessagePriority::class,
            'status' => ClinicalMessageStatus::class,
            'sent_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function hopital(): BelongsTo
    {
        return $this->belongsTo(Configs\Hopital::class);
    }

    public function dossierPatient(): BelongsTo
    {
        return $this->belongsTo(DossierPatient::class);
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(ClinicalMessageRecipient::class);
    }

    public function userStatuses(): HasMany
    {
        return $this->hasMany(MessageUserStatus::class, 'clinical_message_id');
    }

    public function labels(): HasMany
    {
        return $this->hasMany(MessageLabel::class, 'clinical_message_id');
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(MessageMention::class, 'clinical_message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ClinicalMessageAttachment::class);
    }

    public function senderDisplayName(): string
    {
        if ($this->sender_type === 'system') {
            return 'Système IRB';
        }

        return $this->sender?->name ?? 'Utilisateur inconnu';
    }

    public function senderServiceLabel(): string
    {
        return $this->sender?->departement?->name ?? 'Coordination clinique';
    }

    public function excerpt(int $limit = 140): string
    {
        return str($this->body)->squish()->limit($limit)->toString();
    }
}
