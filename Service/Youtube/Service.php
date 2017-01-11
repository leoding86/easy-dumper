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
        private $channels = null;
        private $urls     = null;
        private $ids      = null;
        private $quality  = Youtube::Q1080P;
        private $type     = 'mp4';
        private $extArgsValidation = [
            ['a', '/^download|dump|save$/', 'unkown action'],
            ['u', '/^(?:https:\/\/(?:youtu\.be|w{3}\.youtube\.com),?)+/i', 'invalid video url(s)'],
            ['i', '/^(?:[a-z\d]+,?)+$/i', 'invalid video id(s)'],
            ['t', '/webm|mp4/', 'invalid video type'],
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
                $this->quality = $this->args['q'];
            }

            if (isset($this->args['c'])) {
                $this->channels = explode(',', $this->args['c']);
            }

            $this->youtube = new Youtube();

            if ($this->proxy) {
                $this->youtube->addOptions(['proxy' => $this->proxy]);
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
                        $video_info = $this->youtube->setVideoId($id)->fliteVideoInfo()->getVideoInfo($this->quality);
                        if (!$video_info) {
                            Helper::println('Cannot get video info, url -> ' . $this->youtube->getVideoInfoUrl());
                            continue 2;
                        }
                        break;
                    } catch (YoutubeException $e) {
                        Helper::println($e->getMessage());
                        continue 2;
                    }
                }

                /**
                 * 创建下载工作
                 */
                $download_work = new DownloadWork(
                    $video_info, 
                    $this->saveRootDir, 
                    $this->youtube->headers, 
                    $this->youtube->options
                );
                
                $this->submitWork($download_work);
                $this->wait();
            }

            $this->wait();
            $this->shutdown();
            Helper::println('All done');
        }

        public function dumpAction()
        {
            Helper::println('Not implemented');
            throw new DumperException('Not implemented');
        }

        public function saveAction()
        {
            Helper::println('Not implemented');
            throw new DumperException('Not implemented');
        }
    }
}