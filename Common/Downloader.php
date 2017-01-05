<?php
namespace Common;

class Downloader
{
    const REPLACE_MODE = 1;
    const SKIP_MODE    = 2;
    const RENAME_MODE  = 3;

    const BEFORE_EVENT   = 1;
    const START_EVENT    = 2;
    const PROCESS_EVENT  = 3;
    const COMPLETE_EVENT = 4;
    const FINAL_EVENT    = 5;
    const ERROR_EVENT    = 6;

    private $jobId        = '';
    private $resourceUri  = '';
    private $options      = [];
    private $headers      = [];
    private $saveDir      = '';
    private $saveFile     = '';
    private $saveName     = '';
    private $saveExt      = '';
    private $saveMode     = self::SKIP_MODE;
    private $chunkSize    = 1024 * 1024; // bytes
    private $events       = [];
    private $retryTimes   = 10;
    private $retryCount   = 10; // Same as $this->retryTimes
    private $savedFile    = null;

    private function buildSaveFile()
    {
        $this->saveFile = $this->saveDir . $this->saveName . $this->saveExt;
    }

    private function isSaveFileExists()
    {
        /* 再次检查保存目录，方式目录意外删除 */
        if (!is_dir($this->saveDir)) {
            throw new \Exception('save_dir is missing', 1);
        }

        if (is_file($this->saveFile) && filesize($this->saveFile) > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function retry($reset = false)
    {
        if ($reset) {
            $this->retryCount = $this->retryTimes;
            return true;
        }

        return $this->retryCount-- > 0;
    }

    private function dispatch($event, $arguments = [])
    {
        if (isset($this->events[$event])) {
            foreach ($this->events[$event] as $hook) {
                call_user_func_array($hook, $arguments);
            }
        }
    }

    public function __construct(
        DownloadTask $task,
        $headers   = [],
        $options   = [],
        $save_mode = self::SKIP_MODE
    ) {
        $this->jobId = $task->id;
        $this->setSaveDir($task->saveDir);
        $this->setSaveName($task->saveName);
        $this->setResourceUri($task->resource);
        $this->setSaveMode($save_mode);
        $this->setHeaders($headers);
        $this->setOptions($options);
    }

    public function setSaveDir($save_dir)
    {
        if (!is_dir($save_dir)) {
            throw new \Exception('Invalid save_dir', 1);
        }
        $this->saveDir = str_replace('\\', '/', $save_dir);

        if (substr($this->saveDir, -1) != '/') {
            $this->saveDir .= '/';
        }

        return $this;
    }

    public function setSaveName($save_name)
    {
        if (empty($save_name)) {
            throw new \Exception('save_name cannot be empty', 1);
        }

        if (($pos = strrpos($save_name, '.')) >= 0) {
            $this->saveName = substr($save_name, 0, $pos);
            $this->saveExt  = substr($save_name, $pos);
        } else {
            $this->saveName = $save_name;
        }

        return $this;
    }

    public function setSaveMode($save_mode)
    {
        $this->saveMode = $save_mode;
        return $this;
    }

    public function setResourceUri($uri)
    {
        $this->resourceUri = $uri;
        return $this;
    }

    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }

    public function setChunkSize($size)
    {
        $this->chunkSize = (int)$size;
        return $this;
    }

    public function setRetryTimes($times)
    {
        $this->retryTimes = $times;
        $this->retryCount = $this->retryTimes;
        return $this;
    }

    public function registerHook($event, $hook)
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = [];
        }

        $this->events[$event][] = $hook;
    }

    public function getSavedFile()
    {
        return $this->savedFile;
    }

    public function start()
    {
        try {
            $this->buildSaveFile();

            if ($this->isSaveFileExists()) {
                if ($this->saveMode === self::SKIP_MODE) {
                    $this->dispatch(self::COMPLETE_EVENT, [$this->jobId, true]);
                    return;
                } else if ($this->saveMode === self::REPLACE_MODE) {
                    $filename = $this->saveFile;
                } else if ($this->saveMode === self::RENAME_MODE) {
                    $filename = $this->saveDir . $this->saveName . '_' . time() . $this->saveExt;
                }
            } else {
                $filename = $this->saveFile;
            }

            /* 获得文件大小 */
            while (true) {
                try {
                    $request = \Requests::head($this->resourceUri, $this->headers, $this->options);
                    if ($request->status_code != 200) {
                        throw new \Exception('Cannot get resource head');
                    }
                    $size = $request->headers['content-length'];
                    $this->dispatch(self::BEFORE_EVENT, [$this->jobId, $size, $this->resourceUri]);
                    $this->retry(true);
                    break;
                } catch (\Exception $e) {
                    if (!$this->retry()) {
                        throw $e;
                    }
                }
            }

            $file_handle = fopen($filename, 'w+');

            $range_min = 0;
            $range_max = $this->chunkSize > $size ? $size : $this->chunkSize;
            do {
                $range = 'bytes=' . $range_min . '-' . $range_max;

                $this->headers = array_merge(
                    $this->headers,
                    ['Range' => $range]
                );

                /* 请求数据 */
                while (true) {
                    try {
                        $request = \Requests::get($this->resourceUri, $this->headers, $this->options);
                        if ($request->status_code == 200 || $request->status_code == 206) {
                            $this->dispatch(self::PROCESS_EVENT, [$this->jobId, $range_max, $size]);
                            fwrite($file_handle, $request->body);
                            $this->retry(true);
                        } else {
                            throw new \Exception('Some part of the resource is not downloaded');
                        }
                        unset($request);
                        break;
                    } catch (\Exception $e) {
                        if (!$this->retry()) {
                            throw $e;
                        }
                    }
                }

                $range_min = $range_max + 1;
                $range_max = ($range_max + $this->chunkSize) > $size ? $size : ($range_max + $this->chunkSize);
            } while ($range_min <= $range_max);

            fclose($file_handle);
            $this->savedFile = $filename;
            $this->dispatch(self::COMPLETE_EVENT, [$this->jobId, false]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
