<?php
namespace Common;

abstract class Service
{
    protected $args           = [];
    protected $argsValidation = [];
    protected $saveDir        = null;
    protected $retryTimes     = 3;
    protected $startTime      = 0;
    protected $endTime        = 0;

    abstract protected function parseArgs($args);

    abstract public function start();
}
