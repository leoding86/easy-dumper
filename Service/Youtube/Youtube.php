<?php
namespace Service\Youtube
{
    class Youtube extends \Common\Request
    {
        const Q1080P = '1080p';
        const Q720P  = '720p';
        const Q480P  = '480p';
        const Q360P  = '360p';
        const Q240P  = '240p';
        const Q144P  = '144p';

        private   $pageUrl            = '';
        private   $videoId            = '';
        private   $videoList          = [];
        private   $videoInfoUrl       = '';
        protected $videoInfoUrlFormat = 'http://www.youtube.com/get_video_info?&video_id=%s&asv=3&el=detailpage&hl=en_US';

        private function buildVideoInfoUrl()
        {
            if (empty($this->videoId)) {
                throw new YoutubeException('Video id not pass in');
            }
            $this->videoInfoUrl = sprintf($this->videoInfoUrlFormat, $this->videoId);
        }

        public function __construct($arg = null)
        {
            $this->setHeaders(['User-Agent' => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.102 Safari/537.36"]);

            if (is_null($arg))
                return;

            if (preg_match('/^[\da-z]$/i', $id)) {
                $this->setVideoId($arg);
            } else {
                $this->setPageUrl($arg);
            }
        }

        public function getVideoInfoUrl()
        {
            return $this->videoInfoUrl;
        }

        public function parseIdFromUrl(/*string*/ $url)
        {
            $matches = [];
            if (preg_match('/^https:\/\/w{3}?.youtube.com\/.*v=([\da-z]+)/i', $url, $matches) ||
                preg_match('/^https?:\/\/youtu.be/([\da-z]+)/i', $url, $matches)
            ) {
                return $matches[1];
            } else {
                throw new YoutubeException('Invalid video page url ' . $url);
            }
        }

        public function setPageUrl(/*string*/$url)
        {
            $this->videoId = $this->parseIdFromUrl($url);
            return $this;
        }

        public function setVideoId(/*string*/$id)
        {
            if (!preg_match('/^[\da-z]+$/i', $id)) {
                throw new YoutubeException('Invalid video id ' . $id);
            }

            $this->videoId = $id;
            return $this;
        }

        public function fliteVideoInfo()
        {
            $this->buildVideoInfoUrl();

            try {
                $response = \Requests::get($this->videoInfoUrl, $this->headers, $this->options);
                $video_info = [];
                parse_str($response->body, $video_info);

                /**
                 * 检查请求视频数据是否成功
                 * 特征参数，status=fail为失败
                 */
                if (!isset($video_info['adaptive_fmts']) || (isset($video_info['status']) && $video_info['status'] === 'fail')) {
                    throw new YoutubeException('Cannot get video info. video_info_url: ' . $this->videoInfoUrl);
                }

                /**
                 * 解析视频信息数据
                 */
                $this->videoList = [];
                $qualities = [];
                foreach (explode(',', $video_info['adaptive_fmts']) as $vinfo) {
                    $info = null;
                    parse_str($vinfo, $info);
                    $item_info = [
                        'video_id'  => $this->videoId,
                        'quality'   => !isset($info['quality_label']) ? '' : $info['quality_label'], // 音频没有质量参数
                        'type'      => substr($info['type'], 0, strpos($info['type'], '/')),
                        'ext'       => substr($info['type'], strpos($info['type'], '/') + 1, strpos($info['type'], ';') - strpos($info['type'], '/') - 1),
                        'url'       => $info['url'],
                    ];
                    $this->videoList[] = $item_info;
                    $qualities[] = !isset($info['quality_label']) ? '' : $info['quality_label']; // 记录质量，用于排序
                }
                array_multisort($qualities, SORT_DESC, SORT_NATURAL, $this->videoList); // 按照质量高到低排序
                return $this;
            } catch (\Exception $e) {
                throw new \Common\DumperException($e->getMessage());
            }
        }

        /**
         * 获得指定质量的视频信息
         * 
         * @param  string     $quality video quality
         * @return array|null
         */
        public function getVideoInfo(/*int*/$quality = self::Q1080P)
        {
            $first_video_info = null;
            foreach ($this->videoList as $video_info) {
                if (strtolower($video_info['type']) === 'video') {
                    if (empty($first_video_info)) {
                        $first_video_info = $video_info;
                    }

                    if ($video_info['quality'] == strtolower($quality)) {
                        return $video_info;
                    }
                }
            }

            return $first_video_info;
        }
    }

    class YoutubeException extends \Common\DumperException
    {

    }
}