<?php

namespace App\Services;

use App\Contracts\NotificationGateway;
use App\Models\AutomationDelivery;
use Illuminate\Support\Facades\Log;

class LocalAuditNotificationGateway implements NotificationGateway
{
    public function send(AutomationDelivery $delivery): void
    {
        Log::info('automation.notification_recorded', [
            'delivery_id' => $delivery->id,
            'automation_type' => $delivery->automation_type,
            'recipient_role' => $delivery->recipient_role,
            'correlation_id' => $delivery->correlation_id,
        ]);
    }
}
