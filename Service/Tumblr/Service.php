<?php
namespace Service\Tumblr;

use \Common\Helper;
use \Common\Downloader;
use \Common\DownloadPool;

class Service extends \Common\Service
{
    /**
     * Cli command line example :
     * php service.php -S Tumblr -k [API_KEY] -a dump -s [SAVE_PATH] -p 127.0.0.1:1080 -b [BLOG1,BLOG2] -t [POST_TYPE]
     */
    private $tumblr = null;
    protected $args   = [];
    protected $argsValidation = [
        ['k', '/^[a-z\d]{32,50}$/i',       'api_key is invalid'],
        ['a', '/^dump|save$/',             'unkown action'],
        ['b', '/^([a-z\d\-_]+\.tumblr\.com,?)+$/', 'invalid blog identifier'],
        ['p', '/^.+$/',                    'invalid proxy'],
        ['s', '/^.+$/',                    'invalid save path'],
        ['t', '/^[a-z]+$/',                'invalid post type'],
        ['r', '/^\d+$/',                   'invalid retry times'],
    ];
    protected $saveDir    = null;
    protected $retryTimes = 3;
    protected $startTime = 0;
    protected $endTime = 0;

    protected function parseArgs($args)
    {
        foreach ($args as $name => $value) {
            foreach ($this->argsValidation as $_arg) {
                if ($name == $_arg[0] && !preg_match($_arg[1], $value)) {
                    Helper::printlnExit($_arg[2]);
                }
            }

            $this->args[$name] = $value;
        }

        if (!isset($this->args['b'])) {
            Helper::printlnExit('Blog has not been setted');
        }

        if (!isset($this->args['s'])) {
            Helper::printlnExit('Save path has not been setted');
        }

        if (isset($this->args['r']) && $this->args['r'] > 0) {
            $this->retryTimes = $this->args['r'];
        }
    }

    private function getType()
    {
        return isset($this->args['t']) ? $this->args['t'] : null;
    }

    private function createBlogSaveFolder($blog)
    {
        $this->args['s'] .= substr($this->args['s'], -1) == '/' ? '' : '/';
        if (is_dir($this->args['s'])) {
            $this->saveDir = $this->args['s'] . $blog . '/';
            if (!is_dir($this->saveDir)) {
                /* 创建目录 */
                mkdir($this->saveDir);
            }
        } else {
            Helper::printlnExit('Save path is not a dir');
        }
    }

    private function createPostSaveFolder($post_id)
    {
        if (empty($post_id)) {
            throw new \Exception("post_id missing for creating folder for saving post");
        }

        $save_dir = $this->saveDir . $post_id . '/';
        if (mkdir($save_dir) === false) {
            throw new \Exception("Cannot create folder for saving post [" . $save_dir . "]");
        }

        return $save_dir;
    }

    private function getPosts($blog, $params)
    {
        $retry_times = $this->retryTimes;
        while ($retry_times-- > 0) {
            try {
                $posts = $this->tumblr->setParams($params)->posts($blog, $this->getType());
                if ($posts == null) {
                    Helper::println('Get posts error, skip');
                    yield;
                }
            } catch (\Exception $e) {
                Helper::println($e->getMessage() . ', retry.');

                if ($retry_times <= 0) {
                    Helper::println('Out of retry times, skip');
                    yield;
                }
            }
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

    private function dump()
    {
        foreach (explode(',', $this->args['b']) as $blog) {
            $retry_times = $this->retryTimes;

            do {
                try {
                    Helper::println('Try to get blog %s post count', $blog);
                    $info = $this->tumblr->posts($blog, $this->getType());
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
            $pool = new DownloadPool(50, DownloadWorker::class, [$loaders]);

            /* 循环获得文章内容 */
            for ($offset = 0; $offset < $info->total_posts; $offset += 20) {

                Helper::println('%s %s - %s', $blog, $offset, $offset + 20);

                foreach ($this->getPosts($blog, ['offset' => $offset]) as $post) {
                    if (is_null($post)) {
                        continue;
                    }

                    $save_dir = $this->createPostSaveFolder($post->id); // 创建保存post的文件夹
                    $post->setDownloadOptions($this->tumblr->options);
                    $post->registerHook(Post::BEFORE_DOWNLOAD, [$this, 'beforeDownloadHandler']);

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
        }

        Helper::printlnExit('Completed!');
    }

    public function __construct($args)
    {
        $this->parseArgs($args);
        $this->tumblr = new Api($this->args['k']);
        $this->tumblr->addOptions(['timeout' => 60]);

        if (isset($this->args['p'])) {
            $this->tumblr->addOptions(['proxy' => $this->args['p']]);
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
            Helper::println('Job %s is completed [%s]', $job_id, ($is_skip ? 'skip' : ''));
        });
    }

    public function start()
    {
        if (!isset($this->args['a'])) {
            Helper::println("Has no action", true);
        }
        $this->startTime = time();
        call_user_func([$this, $this->args['a']]);
        $this->endTime = time();

        Helper::println('Time escaped: %s seconds', $this->endTime - $this->startTime);
    }
}
