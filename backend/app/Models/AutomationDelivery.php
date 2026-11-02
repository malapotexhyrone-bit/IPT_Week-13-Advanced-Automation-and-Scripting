<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AutomationDelivery extends Model
{
    public const TYPE_REMINDER = 'office_task_reminder';

    public const TYPE_ESCALATION = 'stalled_task_escalation';

    public const TYPE_COMPLETION = 'clearance_completion';

    public const STATUS_PENDING = 'pending';

    public const STATUS_RETRY_SCHEDULED = 'retry_scheduled';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_HUMAN_ACTION = 'human_action_required';

    protected $fillable = [
        'clearance_id',
        'office_task_id',
        'automation_type',
        'recipient_role',
        'status',
        'idempotency_key',
        'correlation_id',
        'attempt_count',
        'max_attempts',
        'retry_allowed',
        'next_attempt_at',
        'last_error_code',
        'delivered_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'retry_allowed' => 'boolean',
            'next_attempt_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function clearance(): BelongsTo
    {
        return $this->belongsTo(Clearance::class);
    }

    public function officeTask(): BelongsTo
    {
        return $this->belongsTo(OfficeTask::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AutomationAttempt::class);
    }

    public function humanActionItem(): HasOne
    {
        return $this->hasOne(HumanActionItem::class);
    }
}
