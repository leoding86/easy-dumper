<?php
namespace Common
{
    class Request
    {
        public $params    = [];
        public $options = [];
        public $header  = [];

        public function setParams($params)
        {
            $this->params = $params;
            return $this;
        }

        public function setOptions($options)
        {
            $this->options = $options;
            return $this;
        }

        public function addOptions($options)
        {
            $this->options = array_merge($this->options, $options);
            return $this;
        }

        public function removeOptions($options)
        {
            foreach ($this->options as $key => $option) {
                if (in_array($key, $options)) {
                    unset($this->options[$key]);
                }
            }
            return $this;
        }
    }
}