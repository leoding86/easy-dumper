<?php
namespace Common;

abstract class Service
{
    protected $args           = [];
    protected $argsValidation = [
        ['p', '/^.+$/',  'invalid proxy'],       // 代理设置
        ['s', '/^.+$/',  'invalid save path'],   // 保存路径设置
        ['r', '/^\d+$/', 'invalid retry times'], // 重试次数设置
        ['m', '/^\d+$/', 'invalid max post'],    // 下载最大数量设置
    ];
    protected $saveRootDir    = null;
    protected $maxCount       = 0;
    protected $proxy          = null;
    protected $retryTimes     = 3;
    protected $startTime      = 0;
    protected $endTime        = 0;

    public function __construct($args)
    {
        foreach ($args as $name => $value) {
            foreach ($this->argsValidation as $_arg) {
                if ($name == $_arg[0] && !preg_match($_arg[1], $value)) {
                    Helper::printlnExit($_arg[2]);
                }
            }

            $this->args[$name] = $value;
        }

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

    abstract protected function parseArgs($args);

    abstract public function start();
}
