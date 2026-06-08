<?php

namespace App\Models;

use App\Models\Configs\Hopital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationImport extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'hopital_id',
        'original_filename',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'success_count',
        'failed_count',
        'errors_file_path',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hopital(): BelongsTo
    {
        return $this->belongsTo(Hopital::class);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }

    public function progressPercent(): int
    {
        if ($this->total_rows && $this->total_rows > 0) {
            return (int) min(100, round(($this->processed_rows / $this->total_rows) * 100));
        }

        if ($this->status === self::STATUS_COMPLETED) {
            return 100;
        }

        return $this->status === self::STATUS_PROCESSING ? 5 : 0;
    }
}
