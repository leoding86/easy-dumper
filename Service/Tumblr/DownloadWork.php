<?php
namespace Service\Tumblr;

use Common\Helper;

class DownloadWork extends \Threaded
{
    public $post             = null;
    public $saveDir          = null;
    public $isCompleted      = false;

    public function __construct($post, $save_dir)
    {
        $this->post = $post;
        $this->saveDir = $save_dir;
    }

    public function run()
    {
        try {
            $this->post->setSaveDir($this->saveDir)->download();
        } catch (\Exception $e) {
            Helper::println($e->getMessage());
        }
        $this->isCompleted = true;
    }

    public function isCompleted()
    {
        return $this->isCompleted;
    }
}
