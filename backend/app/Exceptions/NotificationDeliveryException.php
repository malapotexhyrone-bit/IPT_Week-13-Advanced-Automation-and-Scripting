<?php

namespace App\Exceptions;

use RuntimeException;

class NotificationDeliveryException extends RuntimeException
{
    public function __construct(
        public readonly string $safeCode,
        public readonly bool $retryable,
    ) {
        parent::__construct('The notification adapter could not complete the delivery.');
    }
}
