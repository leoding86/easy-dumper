<?php
namespace Service\Youtube
{
    use Common\DumperException;
    use Common\Helper;
    use Common\Downloader;
    use Common\DownloadPool;
    use Common\DownloadTask;
    use Webmozart\PathUtil\Path;

    define('SERVICE', 'Youtube'); // 定义服务名称

    class Service extends \Common\Service
    {
        private $youtube  = null;
        private $saveDir  = null;
        private $action   = null;
        private $channels = null;
        private $urls     = null;
        private $ids      = null;
        private $quanlity = Youtube::Q1080P;
        private $extArgsValidation = [
            ['a', '/^download|dump|save|build$/', 'unkown action'],
            ['u', '/^(?:https:\/\/(?:youtu\.be|w{3}\.youtube\.com),?)+/i', 'invalid video url(s)'],
            ['i', '/^(?:[a-z\d]+,?)+$/i', 'invalid video id(s)'],
            ['q', '.+', ''],
            ['c', '.+', ''],
        ];

        public function __construct($args)
        {
            parent::__construct($args);
            $this->parseArgs($args, $this->extArgsValidation);

            /* 行为 */
            if (!isset($this->args['a'])) {
                Helper::printlnExit('Invalid action');
            } else {
                $this->action = $this->args['a'];
            }

            if (isset($this->args['u'])) {
                $this->urls = explode(',', $this->args['u']);
            }

            if (isset($this->args['i'])) {
                $this->ids = explode(',', $this->args['i']);
            }

            if (isset($this->args['q'])) {
                $this->quanlity = $this->args['q'];
            }

            if (isset($this->args['c'])) {
                $this->channels = explode(',', $this->args['c']);
            }

            $this->youtube = new Youtube();

            if ($this->proxy) {
                $this->youtube->addOption(['proxy' => $this->proxy]);
            }
        }

        public function downloadAction()
        {
            $ids = [];
            if (!empty($this->ids)) {
                $ids = $this->ids;
            } else if (!empty($this->urls)) {
                foreach ($this->urls as $url) {
                    $ids[] = $this->youtube->parseIdFromUrl($url);
                }
            } else {
                throw new DumperException('Has no id(s) or url(s) param');
            }

            $this->createPool();
            foreach ($ids as $id) {
                $retry_times = $this->retryTimes;

                while ($retry_times-- > 0) {
                    Helper::println('Try to get video info, id -> ' . $id);
                    try {
                        $video_info = $this->youtube->setVideoId($id)->getVideoInfo($this->quanlity);
                        if (!$video_info) {
                            Helper::println('Cannot get video info, id -> ' . $id);
                            continue 2;
                        }
                    } catch (YoutubeException $e) {
                        Helper::println($e->getMessage());
                        continue 2;
                    }
                }

                $download_task = new DownloadTask(
                    $video_info['video_id'],
                    $this->saveRootDir,
                    $video_info['video_id'] . '.' . $video_info['ext'],
                    $video_info['url']
                );
                $downloader = new Downloader($download_task, $this->youtube->headers, $this->youtube->options);
                $downloader->setChunkSize(1024 * 20);
                $download_work = new DownloadWork($downloader);
                $download_work->setDownloadOptions()
                              ->registerHook(Downloader::BEFORE_EVENT, function($job_id, $total_size, $url) {
                                    Helper::println('Job %s total size: %s bytes [%s]', $job_id, $total_size, $url);
                              })
                              ->registerHook(Downloader::PROCESS_EVENT, function($job_id, $completed_size, total_size) {
                                    Helper::println('Job %s is complete %f%%', $job_id, $completed_size / $total_size * 100);
                              })
                              ->registerHook(Downloader::COMPLETE_EVENT, function($job_id, $is_skip) {
                                    Helper::println('Job %s is completed %s', $job_id, ($is_skip ? '[skip]' : ''));
                              });
                $this->submitWork($download_work);
                $this->wait();
            }

            $this->wait();
            $this->shutdown();
            Helper::println('All done');
        }

        public function dumpAction()
        {

        }

        public function saveAction()
        {

        }

        public function buildAction()
        {

        }
    }
}