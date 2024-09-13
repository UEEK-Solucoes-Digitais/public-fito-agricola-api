<?php

namespace App\Exceptions;

use Exception;
use App\Models\LogError;

class OperationException extends Exception
{

    protected $environment;
    protected $table_name;
    protected $description;

    protected $exception_message;
    protected $exception_file;
    protected $exception_line;

    public function __construct($description, $table_name, $message = '', $code = 500, $environment = 1, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->environment = $environment;
        $this->table_name = $table_name;
        $this->description = $description;

        $this->exception_message = null;
        $this->exception_file = null;
        $this->exception_line = null;
    }

    public function report()
    {
        $this->logError();
    }

    public function catchThrowable($message, $file, $line)
    {
        $this->exception_message = $message;
        $this->exception_file = $file;
        $this->exception_line = $line;

        $this->logError();
    }

    protected function logError()
    {
        $file = $this->exception_file ?? $this->getFile();

        $log = new LogError();
        $log->error_description = $this->description;
        $log->environment = $this->environment;
        $log->table_name = $this->table_name;
        $log->exception_message = $this->exception_message ?? $this->getMessage();
        $log->exception_file = pathinfo($file, PATHINFO_FILENAME);
        $log->exception_line = $this->exception_line ?? $this->getLine();
        $log->save();
    }
}
