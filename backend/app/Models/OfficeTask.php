<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeTask extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'clearance_id',
        'office_id',
        'status',
        'due_at',
        'last_reminded_at',
        'escalated_at',
        'approved_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'last_reminded_at' => 'datetime',
            'escalated_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function clearance(): BelongsTo
    {
        return $this->belongsTo(Clearance::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
