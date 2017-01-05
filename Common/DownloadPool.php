<?php
namespace Common
{
    class DownloadPool extends \Pool
    {
        public function process($work_left = 0)
        {
            while (count($this->work) > $work_left) {
                $this->collect(function ($work) {
                    return $work->isCompleted();
                });
            }
        }
    }
}
