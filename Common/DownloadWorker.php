<?php
namespace Common;

class DownloadWorker extends \Worker
{
    protected $loaders;

    public function __construct($loaders)
    {
        $this->loaders = $loaders;
    }

    public function run()
    {
        /* autoload */
        foreach ($this->loaders as $loader) {
            $loader->register(true);
        }
    }
}
