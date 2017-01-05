<?php
namespace Service\Ph;

use \Common\Helper;
use \Common\Downloader;
use \Common\DownloadPool;

class Service extends \Common\Service
{
    private $args = [];
    private $argsValidation = [];
    private $saveDir = null;
    private $retryTimes = 3;
    private $startTime = 0;
    private $endTime = 0;

    protected function parseArgs($args)
    {
        
    }
}