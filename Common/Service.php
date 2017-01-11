<?php
namespace Common;

abstract class Service
{
    private $argsValidation = [
        ['p', '/^.+$/',  'invalid proxy'],       // 代理设置
        ['s', '/^.+$/',  'invalid save path'],   // 保存路径设置
        ['r', '/^\d+$/', 'invalid retry times'], // 重试次数设置
        ['m', '/^\d+$/', 'invalid max post'],    // 下载最大数量设置
    ];
    private $pool = null;

    protected $args           = [];
    protected $saveRootDir    = null;
    protected $maxCount       = 0;
    protected $proxy          = null;
    protected $retryTimes     = 3;
    protected $action         = null;
    protected $startTime      = 0;
    protected $endTime        = 0;

    protected function parseArgs($args, $validation = null)
    {
        $this->args = [];

        if (is_null($validation)) {
            $validation = $this->argsValidation;
        }

        foreach ($args as $name => $value) {
            foreach ($validation as $_arg) {
                if ($name == $_arg[0] && !preg_match($_arg[1], $value)) {
                    Helper::printlnExit($_arg[2]);
                }
            }

            $this->args[$name] = $value;
        }
    }

    protected function createPool()
    {
        if (!is_null($this->pool)) {
            return;
        }
        $loaders = [require('./vendor/autoload.php'), \Autoloader::getLoader()];
        $this->pool = new DownloadPool(3, DownloadWorker::class, [$loaders]);
    }

    protected function submitWork(\Threaded $work)
    {
        $this->pool->submit($work);
    }

    protected function wait(/*int*/ $work_left = 0)
    {
        $this->pool->process($work_left);
    }

    protected function shutdown()
    {
        $this->pool->shutdown();
    }

    public function __construct($args)
    {
        $this->parseArgs($args);

        /**
         * 处理保存根路径
         * 如果不存在则尝试创建目录
         */
        if (isset($this->args['s'])) {
            $this->saveRootDir = str_replace('\\', '/', $this->args['s']);
            if (substr($this->saveRootDir, -1) === '/') {
                $this->saveRootDir = substr($this->saveRootDir, 0, -1);
            }
        } else {
            $this->saveRootDir = DUMPED . '/' . SERVICE;
        }
        if (!is_dir($this->saveRootDir) && mkdir($this->saveRootDir) === false) {
            Helper::printlnExit('Invalid saveRootDir ' . $this->saveRootDir);
        }

        if (isset($this->args['p'])) {
            $this->proxy = $this->args['p'];
        }

        if (isset($this->args['r']) && $this->args['r'] > 0) {
            $this->retryTimes = $this->args['r'];
        }

        if (isset($this->args['m'])) {
            $this->maxCount = $this->args['m'];
        }
    }

    public function start()
    {
        $this->startTime = time();
        call_user_func([$this, $this->action . 'Action']);
        $this->endTime = time();

        Helper::println('Time escaped: %s seconds', $this->endTime - $this->startTime);
    }
}
