<?php
namespace Common;

class DownloadTask
{
    private $id       = '';
    private $saveDir  = '';
    private $saveName = '';
    private $resource = '';
    private $service  = '';

    public function __get($prop)
    {
        if (is_null($this->$prop)) {
            throw new DownloadTaskException('The property of Task is not exists');
        }

        return $this->$prop;
    }

    public function __set($prop, $value)
    {
        throw new DownloadTaskException('Use set method to set value to prop');
    }

    public function __construct($id, $saveDir, $saveName, $resource, $service = '')
    {
        $this->id       = $id;
        $this->saveDir  = $saveDir;
        $this->saveName = $saveName;
        $this->resource = $resource;
        $this->service  = $service;
    }

    public function setService($service)
    {
        $this->service = $service;
        return $this;
    }

    public function isDone()
    {

    }

    public function markDone()
    {

    }

    public function addRecord()
    {

    }
}

class DownloadTaskException extends DumperException
{

}
