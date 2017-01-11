<?php
namespace Service\Youtube
{
    use Common\Helper;
    use Common\DownloadTask;
    use Common\Downloader;

    class DownloadWork extends \Threaded
    {
        public $isCompleted  = false;
        public $video        = null;
        public $saveDir      = null;
        public $headers      = null;
        public $options      = null;

        public function __construct(array $video, /*string*/ $saveDir, $headers = [], $options = [])
        {
            $this->video = $video;
            $this->saveDir = $saveDir;
            $this->headers = $headers;
            $this->options = $options;
        }

        public function run()
        {
            try {
                $downloadTask = new DownloadTask(
                    $this->video['video_id'],
                    $this->saveDir,
                    $this->video['video_id'] . '.' . $this->video['ext'],
                    $this->video['url']
                );

                $downloader = new Downloader($downloadTask, $this->headers, $this->options);
                $downloader->setChunkSize(1024 * 1024);
                $downloader->registerHook(Downloader::BEFORE_EVENT, function($job_id, $total_size, $url) {
                    Helper::println('Job %s total size: %s bytes [%s]', $job_id, $total_size, $url);
                });

                $downloader->registerHook(Downloader::PROCESS_EVENT, function($job_id, $completed_size, $total_size) {
                    // Helper::println('Job %s is complete %f%%', $job_id, $completed_size / $total_size * 100);
                    Helper::println('%s - %s / %s', $job_id, $completed_size, $total_size);
                });
                $downloader->registerHook(Downloader::COMPLETE_EVENT, function($job_id, $is_skip) {
                    Helper::println('Job %s is completed %s', $job_id, ($is_skip ? '[skip]' : ''));
                });
                $downloader->start();
                $this->isCompleted = true;
            } catch (\Exception $e) {
                $this->isCompleted = true;
                throw new \Common\DumperException($e->getMessage());
            }
        }

        public function isCompleted()
        {
            return $this->isCompleted;
        }
    }
}