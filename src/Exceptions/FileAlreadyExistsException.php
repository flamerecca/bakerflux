<?php

namespace Flamerecca\Bakerflow\Exceptions;

use Throwable;

class FileAlreadyExistsException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}