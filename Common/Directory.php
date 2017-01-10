<?php
namespace Common
{
    class Directory
    {
        private $dir;
        private $dirHandle;
        private $files;

        public function __construct(/* string */$dir)
        {
            if (!is_dir($dir)) {
                throw new DirectoryException('Invalid directory ' . $dir);
            }

            $this->dir = str_replace('\\', '/', $dir);
            if (substr($this->dir, -1) === '/') {
                $this->dir = substr($this->dir, 0, -1);
            }

            $this->dirHandle = opendir($this->dir);
            $this->files     = [];
        }

        public function setDir(/* string */$dir)
        {
            self::__construct($dir);
            return $this;
        }

        public function getFiles()
        {
            return $this->files;
        }

        public function fliteFiles(Directory\Fliter $Fliter = null)
        {
            $this->files = [];
            rewinddir($this->dirHandle);
            while (false !== ($filename = readdir($this->dirHandle))) {
                $file = $this->dir . '/' . $filename;

                if (!is_file($file)) {
                    continue;
                }

                if (!is_null($Fliter)) {
                    if ($Fliter->isMatch($filename)) {
                        $this->files[] = $file;
                    }
                } else {
                    $this->files[] = $file;
                }
            }

            return $this;
        }

        public function getDirectories()
        {
            $directories = [];
            rewinddir($this->dirHandle);
            while (false !== ($filename = readdir($this->dirHandle))) {
                $directory = $this->dir . '/' . $filename;

                if ($filename === '.' || $filename === '..') {
                    continue;
                }

                if (is_dir($directory)) {
                    $directories[] = $directory;
                }
            }

            return $directories;
        }
    }

    class DirectoryException extends DumperException
    {

    }
}
