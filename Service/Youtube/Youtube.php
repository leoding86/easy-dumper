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

        public function __construct($arg)
        {
            if (preg_match('/^[\da-z]$/i', $id)) {
                $this->setVideoId($arg);
            } else {
                $this->setPageUrl($arg);
            }
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
            if (!preg_match('/^[\da-z]$/i', $id)) {
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
                $quanlities = [];
                foreach (explode(',', $params) as $info) {
                    $item_info = [
                        'video_id'  => $this->videoId,
                        'quanlity'  => $info['quanlity_label'],
                        'type'      => substr($info['type'], 0, strpos($info['type'], '/')),
                        'ext'       => substr($info['type'], strpos($info['type'], '/') + 1, strpos($info['type'], ';'))
                        'url'       => $info['url'],
                    ];
                    $quanlities[] = $info['quanlity_label']; // 记录质量，用于排序
                }
                $this->videoList[] = $item_info;
                array_multisort($quanlities, SORT_DESC, SORT_NATURAL, $this->videoList); // 按照质量高到低排序
            } catch (\Exception $e) {
                
            }
        }

        /**
         * 获得指定质量的视频信息
         * 
         * @param  string     $quanlity video quanlity
         * @return array|null
         */
        public function getVideoInfo(/*int*/$quanlity = self::Q1080P)
        {
            $first_video_info = null;
            foreach ($this->videoList as $video_info) {
                if (strtolower($video_info['type']) === 'video') {
                    if (empty($first_video_info)) {
                        $first_video_info = $video_info;
                    }

                    if ($video_info['quanlity'] == strtolower($quanlity)) {
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