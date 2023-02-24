<?php

namespace Aharon;

use Exception;
use Throwable;

class TableNotFoundException extends Exception
{
    private string $tableName;

    public function __construct(string $tableName, int $code = 0, ?Throwable $previous = null)
    {
        $this->tableName = $tableName;
        parent::__construct("'$tableName' table not found.", $code, $previous);
    }
}