<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HumanActionItem extends Model
{
    protected $fillable = [
        'owner_role',
        'reason_code',
        'status',
        'next_step',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(AutomationDelivery::class, 'automation_delivery_id');
    }
}
