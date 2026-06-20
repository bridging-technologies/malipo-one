<?php

namespace MalipoOne\Exceptions;

class ValidationException extends MalipoOneException
{
    public function getFieldErrors(): array
    {
        return $this->getErrors()['errors'] ?? [];
    }
}
