<?php
namespace Service\Tumblr;

use Common\DumperException;
use Requests;
use Webmozart\PathUtil\Path;

class Video extends Post implements \ISaveable
{
    private $savedVideo = null;
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
             * <video  id='***' class='crt-video crt-skin-default' width='250' height='141' poster='***' preload='none' muted data-crt-video data-crt-options='{"autoheight":null,"duration":30,"hdUrl":"***","filmstrip":{"url":"***","width":"200","height":"112"}}' >
             *     <source src="https://api.tumblr.com/video_file/***" type="video/mp4">
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
        $video_relative_path = Path::makeRelative($this->savedVideo, $this->saveDir);

        $template = new \Common\Template();
        $template->setTemplate('Video')
                 ->assign('video', $video_relative_path)
                 ->assign('caption', $this->caption)
                 ->render();
        $html_handle = fopen($this->saveDir . '/index.html', 'w');
        fwrite($html_handle, $template->getContent());
        fclose($html_handle);
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

    public function download()
    {
        $video_info = $this->getVideoInfo();

        if (empty($video_info)) {
            return;
        }

        try {
            $resource_uri = $video_info['url'];
            $save_name = $this->id . '.' . $video_info['type'];

            /**
             * 创建一个下载任务并检查任务是否完成过
             * 如果是新的则添加一条任务记录
             */
            $downloadTask = new \Common\DownloadTask(md5($resource_uri), $this->saveDir, $save_name, $resource_uri);
            if ($downloadTask->isDone()) {
                return;
            }
            $downloadTask->addRecord();

            /* 开始下载 */
            $downloader = new \Common\Downloader($downloadTask, [], $this->downloadOptions);
            $downloader->setChunkSize(1024 * 1024);
            $this->dispatch(self::BEFORE_DOWNLOAD_EVENT, [&$downloader]);
            $downloader->start();
            $this->savedVideo = $downloader->getSavedFile();
            $this->saveAsHtml();
        } catch (\Exception $e) { // 下载失败
            throw new DumperException($e->getMessage());
        }

        $downloadTask->markDone();
    }

    public function save($service)
    {
        throw new DumperException("Not implements", 1);
    }
}