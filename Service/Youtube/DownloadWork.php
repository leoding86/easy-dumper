<?php
namespace Service\Youtube
{
    class DownloadWork extends \Threaded
    {
        public $isCompleted = false;
        public $downloader  = null;

        public function __construct(\Common\Downloader $downloader)
        {
            $this->downloader = $this->downloader;
        }

        public function registerHook(/*string*/$event, $callable)
        {
            $this->downloader($event, $callable);
        }

        public function run()
        {
            try {
                $this->downloader->start();
            } catch (\Exception $e) {
                Helper::println($e->getMessage());
            }
            $this->isCompleted = true;
        }
    }
}