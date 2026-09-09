<?php

namespace Modules\NsOxen\Services;

use RuntimeException;

class OxenException extends RuntimeException
{
    public function __construct( public readonly string $errorCode, string $message, public readonly int $httpStatus = 422 )
    {
        parent::__construct( $message );
    }
}
