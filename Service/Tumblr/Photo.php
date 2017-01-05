<?php
namespace Service\Tumblr;

class Photo extends Post implements \ISaveable
{
    public $photos      = null;
    public $caption     = null;
    public $savedPhotos = [];

    /**
     * 保存内容到html文件
     * 
     * @return void
     */
    private function saveAsHtml()
    {
        
    }

    public function __construct(
        $blog_name    = null,
        $id           = null,
        $post_url     = null,
        $type         = null,
        $timestamp    = null,
        $date         = null,
        $format       = null,
        $photos       = null,
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

        $this->photos      = $photos;
        $this->caption     = $caption;
    }

    public function download($save_dir)
    {
        $count = 0;
        foreach ($this->resourcesUri() as $resource_uri) {
            $ext = null;
            if (($pos = strrpos($resource_uri, '.')) > 0) {
                $ext = strtolower(substr($resource_uri, $pos));
            }

            if (!preg_match('/^\.[a-z]+$/', $ext)) {
                $ext = '';
            }

            $save_name = implode('-', array($this->blogName, $this->id, ++$count)) . $ext;

            try {
                /**
                 * 创建一个下载任务并检查任务是否完成过
                 * 如果是新的则添加一条任务记录
                 */
                $downloadTask = new \Common\DownloadTask(md5($resource_uri), $save_dir, $save_name, $resource_uri);
                if ($downloadTask->isDone()) {
                    $downloadTask = null;
                    unset($downloadTask);
                    continue;
                }
                $downloadTask->addRecord();

                $downloader = new \Common\Downloader($downloadTask, [], $this->downloadOptions);

                $this->dispatch(self::BEFORE_DOWNLOAD_EVENT, [&$downloader]);
                $downloader->start();
                $this->savedPhotos[] = $downloader->getSavedFile();
            } catch (\Exception $e) { // 下载失败
                throw new \Exception($e->getMessage());
            }

            $downloadTask->markDone();
        }
    }

    public function save($service)
    {
        throw new \Exception("Not implements", 1);
    }

    public function resourcesUri()
    {
        $resources_uri = [];
        foreach ($this->photos as $photo) {
            $resources_uri[] = $photo->alt_sizes[0]->url;
        }
        return $resources_uri;
    }
}