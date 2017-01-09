<?php
namespace Service\Tumblr;

use Common\DumperException;

class Post implements \ISaveable
{
    const BEFORE_DOWNLOAD_EVENT = 1;

    protected $downloadOptions = [];
    protected $saveDir = null;

    public $blogName    = null;
    public $id          = null;
    public $postUrl     = null;
    public $type        = null;
    public $timestamp   = null;
    public $date        = null;
    public $format      = null;
    public $tags        = null;
    public $bookmarklet = null;
    public $mobile      = null;
    public $sourceUrl   = null;
    public $sourceTitle = null;
    public $liked       = null;
    public $state       = null;

    public function __construct(
        $blog_name    = null,
        $id           = null,
        $post_url     = null,
        $type         = null,
        $timestamp    = null,
        $date         = null,
        $format       = null,
        $tags         = array(),
        $bookmarklet  = null,
        $mobile       = null,
        $source_url   = null,
        $source_title = null,
        $liked        = null,
        $state        = null
    ) {
        $this->blogName    = $blog_name;
        $this->id          = $id;
        $this->postUrl     = $post_url;
        $this->type        = $type;
        $this->timestamp   = $timestamp;
        $this->date        = $date;
        $this->format      = $format;
        $this->tags        = $tags;
        $this->bookmarklet = $bookmarklet;
        $this->mobile      = $mobile;
        $this->sourceUrl   = $source_url;
        $this->sourceTitle = $source_title;
        $this->liked       = $liked;
        $this->state       = $state;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function setSaveDir($save_dir)
    {
        $this->saveDir = $save_dir;
        return $this;
    }

    public function setDownloadOptions($options)
    {
        $this->downloadOptions = $options;
    }

    public function download()
    {
        throw new DumperException("Not implements", 1);
    }

    public function save($service)
    {
        throw new DumperException("Not implements", 1);
    }

    private $events = [];

    public function registerHook($event, $hook)
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = [];
        }

        if (is_callable($hook)) {
            $this->events[$event][] = $hook;
        }
    }

    public function dispatch($event, $params = [])
    {
        if (isset($this->events[$event])) {
            foreach ($this->events[$event] as $hook) {
                call_user_func_array($hook, $params);
            }
        }
    }
}
