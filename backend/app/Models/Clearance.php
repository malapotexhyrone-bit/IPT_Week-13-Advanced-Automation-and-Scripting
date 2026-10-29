<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clearance extends Model
{
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'student_id',
        'academic_term_id',
        'status',
        'correlation_id',
        'idempotency_key_hash',
        'initiated_at',
        'completed_at',
    ];

    protected $hidden = ['idempotency_key_hash'];

    protected function casts(): array
    {
        return [
            'initiated_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function officeTasks(): HasMany
    {
        return $this->hasMany(OfficeTask::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ClearanceEvent::class);
    }
}
