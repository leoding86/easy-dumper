<?php
namespace Service\Tumblr;

use \Requests;

class Video extends Post implements \ISaveable
{
    public $player   = null;
    public $videoUrl = null;
    public $caption  = null;

    private function getVideoType()
    {
        $type_patterns = [
            'Vine'      => null,
            'Instagram' => null,
            'flicker'   => null,
            'vimeo'     => null,

            /**
             * <video  id='embed-583fca6023862368590190' class='crt-video crt-skin-default' width='250' height='141' poster='https://68.media.tumblr.com/tumblr_ohabsuZUDi1rdkpu1_smart1.jpg' preload='none' muted data-crt-video data-crt-options='{"autoheight":null,"duration":30,"hdUrl":"https:\/\/api.tumblr.com\/video_file\/t:cyleRWsXtpWY5LrNY871cA\/153810401050\/tumblr_ohabsuZUDi1rdkpu1","filmstrip":{"url":"https:\/\/66.media.tumblr.com\/previews\/tumblr_ohabsuZUDi1rdkpu1_filmstrip.jpg","width":"200","height":"112"}}' >
             *     <source src="https://api.tumblr.com/video_file/t:cyleRWsXtpWY5LrNY871cA/153810401050/tumblr_ohabsuZUDi1rdkpu1/480" type="video/mp4">
             * </video>
             */
            'Tumblr'    => '/api\.tumblr\.com\/video_file/',
        ];

        foreach ($type_patterns as $type => $pattern) {
            if ($pattern !== null) {
                if (preg_match($pattern, $this->player[0]->embed_code)) {
                    return $type;
                }
            }
        }

        return null;
    }

    private function getVideoInfo()
    {
        if ($type = $this->getVideoType()) {
            $method = 'parse' . $type . 'VideoInfo';
            if (method_exists($this, $method)) {
                return call_user_func([$this, $method]);
            }
        }

        return [];
    }

    /**
     * 保存内容到html文件
     * 
     * @return void
     */
    private function saveAsHtml()
    {

    }

    private function parseTumblrVideoInfo()
    {
        $info = ['type' => '', 'url' => ''];
        $pattern = '/<source\s+src="([^\s]+?)(?:\/\d+)?"[^<>]+type="video\/([^\s]+)"/';
        $matches = [];
        if (preg_match($pattern, $this->player[0]->embed_code, $matches)) {
            $info['type'] = $matches[2];
            $info['url']  = $matches[1];
        }

        /* 获得真实地址 */
        $request = \Requests::head($info['url'], [], $this->downloadOptions);
        if ($request->redirects > 0) {
            $info['url'] = $request->url;
        }

        return $info;
    }

    public function __construct(
        $blog_name    = null,
        $id           = null,
        $post_url     = null,
        $type         = null,
        $timestamp    = null,
        $date         = null,
        $format       = null,
        $player       = null,
        $video_url    = null,
        $caption      = null,
        $tags         = array(),
        $bookmarklet  = null,
        $mobile       = null,
        $source_url   = null,
        $source_title = null,
        $liked        = null,
        $state        = null
    ) {
        parent::__construct(
            $blog_name,
            $id,
            $post_url,
            $type,
            $timestamp,
            $date,
            $format,
            $tags,
            $bookmarklet,
            $mobile,
            $source_url,
            $source_title,
            $liked,
            $state
        );

        $this->player      = $player;
        $this->videoUrl    = $video_url;
        $this->caption     = $caption;
    }

    public function download($save_dir)
    {
        $video_info = $this->getVideoInfo();

        if (empty($video_info)) {
            return;
        }

        try {
            /**
             * 创建一个下载任务并检查任务是否完成过
             * 如果是新的则添加一条任务记录
             */
            $downloadTask = new \Common\DownloadTask(md5($resource_uri), $save_dir, $save_name, $resource_uri);
            if ($downloadTask->isDone()) {
                return;
            }
            $downloadTask->addRecord();

            /* 开始下载 */
            $downloader = new \Common\Downloader(
                md5($video_info['url']),
                $save_dir,
                $this->blogName . '-' . $this->id . '.' . $video_info['type'],
                $video_info['url'],
                [],
                $this->downloadOptions
            );
            $downloader->setChunkSize(1024 * 1024);
            $this->dispatch(self::BEFORE_DOWNLOAD_EVENT, [&$downloader]);
            $downloader->start();
            $this->saveAsHtml();
        } catch (\Exception $e) { // 下载失败
            throw new \Exception($e->getMessage());
        }

        $downloadTask->markDone();
    }

    public function save($service)
    {
        throw new \Exception("Not implements", 1);
    }
}