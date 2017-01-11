<?php
namespace Common
{
    class Request
    {
        public $params    = [];
        public $options = [];
        public $headers  = [];

        public function setParams($params)
        {
            $this->params = $params;
            return $this;
        }

        public function setHeaders(array $headers)
        {
            $this->headers = $headers;
            return $this;
        }

        public function addHeaders(array $headers)
        {
            $this->headers = array_merge($this->headers, $headers);
            return $this;
        }

        public function removeHeaders(array $header_names)
        {
            foreach ($this->headers as $name => $value) {
                if (in_array($name, $header_names)) {
                    unset($this->headers[$name]);
                }
            }
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