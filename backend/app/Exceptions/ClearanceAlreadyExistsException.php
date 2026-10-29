<?php

namespace App\Exceptions;

use App\Models\Clearance;
use RuntimeException;

class ClearanceAlreadyExistsException extends RuntimeException
{
    public function __construct(public readonly Clearance $clearance)
    {
        parent::__construct('A clearance already exists for this term.');
    }
}
