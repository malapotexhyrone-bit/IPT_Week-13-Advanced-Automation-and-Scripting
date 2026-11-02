<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficeTask extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_DEFICIENCY = 'deficiency';

    public const STATUS_AWAITING_RE_REVIEW = 'awaiting_re_review';

    public const STATUS_APPROVED = 'approved';

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

    public function deficiencies(): HasMany
    {
        return $this->hasMany(Deficiency::class);
    }
}
