<?php

namespace App\Contracts;

use App\Models\AutomationDelivery;

interface NotificationGateway
{
    public function send(AutomationDelivery $delivery): void;
}
