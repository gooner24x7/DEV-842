<?php
declare(strict_types=1);

namespace App\Exceptions;

class InvalidRequestException extends \Exception
{
    public function __construct(string $message = 'Invalid request', $code = 400)
    {
        parent::__construct($message, $code);
    }
}
