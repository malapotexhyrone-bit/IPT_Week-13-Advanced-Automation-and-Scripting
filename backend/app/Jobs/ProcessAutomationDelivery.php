<?php

namespace App\Jobs;

use App\Models\AutomationDelivery;
use App\Services\AutomationDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAutomationDelivery implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $deliveryId) {}

    public function handle(AutomationDeliveryService $service): void
    {
        $delivery = AutomationDelivery::query()->find($this->deliveryId);

        if ($delivery !== null) {
            $service->process($delivery);
        }
    }
}
