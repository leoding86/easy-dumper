<?php
namespace Common;

class DumperException extends \Exception
{
    private $errStr      = '';
    private $serviceName = '';

    private function recordErrStr()
    {
        $log_file = RUNTIME . '/' . $this->serviceName . '.log';
        $fmode = is_file($log_file) ? 'a' : 'w';
        $log_handle = fopen($log_file, $fmode);
        fwrite($log_handle, $this->errStr . PHP_EOL);
        fclose($log_handle);
    }

    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
        $this->serviceName = defined('SERVICE') ? SERVICE : 'COMMON';
        $this->errStr  = '[' . $this->serviceName . ']' .
                         '[' . date('Y-m-d H:i:s') . ']' . get_class($this) .
                         " '{$this->message}' in {$this->file}({$this->line})\n" .
                         "{$this->getTraceAsString()}";
        $this->recordErrStr();
    }

    public function __toString()
    {
        return $this->errStr;
    }
}