<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecureExchangeReceipt extends Model
{
    protected $fillable = [
        'message_id',
        'envelope_hash',
        'sender',
        'recipient',
        'clearance_reference',
        'overall_status',
        'issued_at',
        'expires_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }
}
