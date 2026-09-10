<?php
declare(strict_types=1);

namespace App\Http\Exception;

use Throwable;

class NotAuthorizedException extends \Exception
{
    public function __construct($message = "Forbidden", $code = 403, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
