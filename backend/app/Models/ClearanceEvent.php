<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClearanceEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'clearance_id',
        'office_task_id',
        'actor_user_id',
        'event_type',
        'correlation_id',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function clearance(): BelongsTo
    {
        return $this->belongsTo(Clearance::class);
    }
}
