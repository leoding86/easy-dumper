<?php
namespace Service\Tumblr;

use Common\DumperException;
use Common\Helper;
use Common\Downloader;
use Common\DownloadPool;
use Webmozart\PathUtil\Path;

define('SERVICE', 'Tumblr'); // 定义服务名称

class Service extends \Common\Service
{
    private $tumblr  = null;
    private $saveDir = null;
    private $apiKey  = null;
    private $action  = null;
    private $blogs   = [];
    private $postType = null; 
    protected $argsValidation = [
        ['k', '/^[a-z\d]{32,50}$/i',       'api_key is invalid'],
        ['a', '/^dump|save|build$/',             'unkown action'],
        ['b', '/^([a-z\d_-]+\.tumblr\.com,?)+$/', 'invalid blog identifier'],
        ['t', '/^[a-z]+$/',                'invalid post type'],
    ];

    protected function parseArgs($args)
    {
        $this->args = []; // 重置参数集合

        foreach ($args as $name => $value) {
            foreach ($this->argsValidation as $_arg) {
                if ($name == $_arg[0] && !preg_match($_arg[1], $value)) {
                    Helper::printlnExit($_arg[2]);
                }
            }

            $this->args[$name] = $value;
        }

        /* apiKey */
        if (!isset($this->args['k'])) {
            Helper::printlnExit('Api key is not valid');
        } else {
            $this->apiKey = $this->args['k'];
        }

        /* 行为 */
        if (!isset($this->args['a'])) {
            Helper::printlnExit('Invalid action');
        } else {
            $this->action = $this->args['a'];
        }

        /* 处理博客设置 */
        if (!isset($this->args['b'])) {
            Helper::printlnExit('Blog has not been setted');
        } else {
            $this->blogs = explode(',', $this->args['b']);
        }

        /* 文章类型 */
        if (isset($this->args['t'])) {
            if (!in_array($this->args['t'], ['photo', 'video'])) {
                Helper::printlnExit('Invalid post type');
            }
        }
    }

    /**
     * 创建博客文件夹
     * 
     * @param  string $blog 博客名字
     * @return void
     */
    private function createBlogSaveFolder($blog)
    {
        if (is_dir($this->saveRootDir)) {
            $this->saveDir = $this->saveRootDir . '/' . $blog;
            if (!is_dir($this->saveDir)) {
                /* 创建目录 */
                mkdir($this->saveDir);
            }
        } else {
            Helper::printlnExit('Save path is not a dir');
        }
    }

    /**
     * 创建保存文件的文章文件夹
     * 
     * @param  string $post_id 文章id
     * @throws \Common\DumperException dumper异常
     * @return string                  文件夹路径
     */
    private function createPostSaveFolder($post_id)
    {
        if (empty($post_id)) {
            throw new DumperException("post_id missing for creating folder for saving post");
        }

        $save_dir = $this->saveDir . '/' . $post_id . '/';
        if (!is_dir($save_dir) && mkdir($save_dir) === false) {
            throw new DumperException("Cannot create folder for saving post [" . $save_dir . "]");
        }

        return $save_dir;
    }

    private function getPosts($blog, $params)
    {
        $retry_times = $this->retryTimes;
        while ($retry_times-- > 0) {
            $posts = null;
            try {
                $posts = $this->tumblr->setParams($params)->posts($blog, $this->postType);
                if ($posts == null) {
                    Helper::println('Get posts error, skip');
                    yield null;
                }
            } catch (\Exception $e) {
                Helper::println($e->getMessage() . ', retry.');

                if ($retry_times <= 0) {
                    Helper::println('Out of retry times, skip');
                    yield null;
                }
            }
        }

        /**
         * 遍历前检查posts是否有效
         */
        if (is_null($posts)) {
            Helper::println('Has no post');
            yield null;
        }

        foreach ($posts->posts as $p) {
            $post = null;
            switch ($p->type) {
                case 'photo':
                    $post = new Photo(
                        $p->blog_name,
                        $p->id,
                        $p->post_url,
                        $p->type,
                        $p->timestamp,
                        $p->date,
                        $p->format,
                        $p->photos,
                        $p->caption
                    );
                    break;
                case 'video':
                    $post = new Video(
                        $p->blog_name,
                        $p->id,
                        $p->post_url,
                        $p->type,
                        $p->timestamp,
                        $p->date,
                        $p->format,
                        $p->player
                    );
                    break;
            }

            if ($post !== null) {
                yield $post;
            }
        }
    }

    /**
     * 生成入口页面
     * 
     * @return void
     */
    private function createEntryPage()
    {
        $directory = new \Common\Directory($this->saveDir);
        $directories = $directory->getDirectories();
        $posts = [];

        $photo_fliter = new \Common\Directory\Fliter(['jpg', 'jpeg', 'gif', 'png', 'bmp']);
        $video_fliter = new \Common\Directory\Fliter(['mp4', 'flv', 'swf']);
        $text_fliter = new \Common\Directory\Fliter(['txt']);
        foreach ($directories as $dir) {
            $post_info = [];
// Helper::println($dir);
            $directory->setDir($dir);
            if (!empty($directory->fliteFiles($photo_fliter)->getFiles())) {
                $post_info = ['type' => 'photo', 'count' => count($directory->getFiles())];
            } else if (!empty($directory->fliteFiles($video_fliter)->getFiles())) {
                $post_info = ['type' => 'video', 'count' => count($directory->getFiles())];
            } else if (!empty($directory->fliteFiles($text_fliter)->getFiles())) {
                $post_info = ['type' => 'text', 'count' => count($directory->getFiles())];
            }

            if (!empty($post_info)) {
                $post_info['url'] = Path::makeRelative($dir, $this->saveDir) . '/index.html';
                $posts[] = $post_info;
            }
        }

        $template = new \Common\Template();
        $template->setTemplate('entry')
                 ->assign('posts', $posts)
                 ->render();
        $html_handle = fopen($this->saveDir . '/index.html', 'w');
        fwrite($html_handle, $template->getContent());
        fclose($html_handle);
    }

    private function dump()
    {
        foreach ($this->blogs as $blog) {
            $retry_times = $this->retryTimes;
            $max_count = $this->maxCount;

            do {
                try {
                    Helper::println('Try to get blog %s post count', $blog);
                    $info = $this->tumblr->posts($blog, $this->postType);
                    break;
                } catch (\Exception $e) {
                    Helper::println($e->getMessage() . ', retry.');

                    if ($retry_times <= 0) {
                        Helper::println('Get blog info failed.');
                        continue 2;
                    }
                }
            } while ($retry_times-- > 0);

            /* 获得文章数量 */
            Helper::println('%s Has %d posts', $blog, $info->total_posts);

            /* 创建保存文件夹 */
            $this->createBlogSaveFolder($blog);

            /* 创建线程池 */
            $loaders = [require('./vendor/autoload.php'), \Autoloader::getLoader()];
            $pool = new DownloadPool(3, DownloadWorker::class, [$loaders]);

            /* 循环获得文章内容 */
            for ($offset = 0; $offset < $info->total_posts; $offset += 20) {
                Helper::println('%s %s - %s', $blog, $offset, $offset + 20);

                foreach ($this->getPosts($blog, ['offset' => $offset]) as $post) {
                    if ($this->maxCount > 0 && $max_count-- < 0) {
                        break 2;
                    }

                    if (is_null($post)) {
                        continue;
                    }

                    $save_dir = $this->createPostSaveFolder($post->id); // 创建保存post的文件夹
                    $post->setDownloadOptions($this->tumblr->options);
                    $post->registerHook(Post::BEFORE_DOWNLOAD_EVENT, [$this, 'beforeDownloadHandler']);

                    /**
                     * 创建一个任务并提交
                     */
                    $download_work = new DownloadWork($post, $save_dir);
                    $pool->submit($download_work);
                }

                $pool->process(2); // 同步主线程
            }

            $pool->process(); // 同步所有任务
            $pool->shutdown();
            Helper::println('Create ' . $blog . ' entry page file');
            $this->createEntryPage();
        }
        Helper::printlnExit('Completed!');
    }

    public function build()
    {
        foreach ($this->blogs as $blog) {
            $this->createBlogSaveFolder($blog);
            Helper::println('Create ' . $blog . ' entry page file');
            $this->createEntryPage();
            Helper::printlnExit('Completed!');
        }
    }

    public function __construct($args)
    {
        parent::__construct($args);
        $this->parseArgs($args);
        $this->tumblr = new Api($this->apiKey);
        $this->tumblr->addOptions(['timeout' => 60]);

        if (!is_null($this->proxy)) {
            $this->tumblr->addOptions(['proxy' => $this->proxy]);
        }
    }

    public function beforeDownloadHandler($downloader)
    {
        $downloader->setOptions($this->tumblr->options);
        $downloader->setChunkSize(1024 * 1024);
        $downloader->registerHook(Downloader::BEFORE_EVENT, function($job_id, $total_size, $uri) {
            Helper::println('Job %s total size: %s bytes [%s]', $job_id, $total_size, $uri);
        });
        $downloader->registerHook(Downloader::PROCESS_EVENT, function($job_id, $completed_size, $total_size) {
            Helper::println('Job %s is complete %f%%', $job_id, $completed_size / $total_size * 100);
        });
        $downloader->registerHook(Downloader::COMPLETE_EVENT, function($job_id, $is_skip) {
            Helper::println('Job %s is completed %s', $job_id, ($is_skip ? '[skip]' : ''));
        });
    }

    public function start()
    {
        $this->startTime = time();
        call_user_func([$this, $this->args['a']]);
        $this->endTime = time();

        Helper::println('Time escaped: %s seconds', $this->endTime - $this->startTime);
    }
}
