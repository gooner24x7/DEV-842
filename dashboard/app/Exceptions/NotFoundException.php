<?php
declare(strict_types=1);

namespace App\Exceptions;

class NotFoundException extends \Exception
{
    public function __construct(string $message = 'Not found', $code = 404)
    {
        parent::__construct($message, $code);
    }
}
