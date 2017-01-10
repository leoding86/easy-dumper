<?php
namespace Common\Directory
{
    class Fliter
    {
        private $exts = [];

        public function __construct(array $exts)
        {
            foreach ($exts as $ext) {
                $this->exts[] = strtolower($ext);
            }
        }

        public function isMatch($filename)
        {
            $dot_pos = strrpos($filename, '.');
            $ext_name = $dot_pos === false ? '' : strtolower(substr($filename, $dot_pos + 1));

            if (in_array($ext_name, $this->exts)) {
                return true;
            } else {
                return false;
            }
        }
    }
}