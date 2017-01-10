<?php
namespace Service\Youtube
{
    class Youtube extends \Common\Requests
    {
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

        public function __construct()
        {

        }

        public function setPageUrl(/*string*/$url)
        {
            $matches = [];
            if (preg_match('/^https:\/\/w{3}?.youtube.com\/.*v=([\da-z]+)/i', $url, $matches) ||
                preg_match('/^https?:\/\/youtu.be/([\da-z]+)/i', $url, $matches)
            ) {
                $this->videoId = $matches[1];
            } else {
                throw new YoutubeException('Invalid video page url ' . $url);
            }
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
                $video_list = [];
                foreach (explode(',', $params) as $info) {
                    $item_info = [
                        'quanlity'  => $info['quanlity_label'],
                        'type'      => $info['type']
                    ];
                }
            } catch (\Exception $e) {
                
            }
        }
    }

    class YoutubeException extends \Common\DumperException
    {

    }
}