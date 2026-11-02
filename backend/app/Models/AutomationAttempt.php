<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationAttempt extends Model
{
    protected $fillable = [
        'attempt_number',
        'outcome',
        'error_code',
        'correlation_id',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return ['attempted_at' => 'datetime'];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(AutomationDelivery::class, 'automation_delivery_id');
    }
}
