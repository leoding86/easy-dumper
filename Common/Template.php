<?php
namespace Common
{
    class Template
    {
        private $assigns      = [];
        private $templateName = '';
        private $templatePath = '';
        private $templateExt  = '';
        private $content      = '';

        public function __construct($template_path = null, $template_ext = '.html')
        {
            $this->templatePath = is_null($template_path) ?
                                  (TEMPLATE . '/' . SERVICE ) : str_replace('\\', '/', $template_path);

            if (!is_dir($this->templatePath)) {
                throw new TemplateException('template_path is not a valid dir');
            }
            $this->templatePath .= substr($this->templatePath, -1) == '/' ? '' : '/';
            $this->templateExt  = $template_ext;
        }

        public function assign($name, $value)
        {
            $this->assigns[$name] = $value;
            return $this;
        }

        public function setTemplate($template_name)
        {
            $this->templateName = $template_name;
            return $this;
        }

        public function getContent()
        {
            return $this->content;
        }

        public function render()
        {
            $template_file = $this->templatePath . $this->templateName . $this->templateExt;
            if (!is_file($template_file)) {
                throw new TemplateException('template is not a valid file');
            }

            foreach ($this->assigns as $name => $value) {
                ${$name} = $value;
            }

            /**
             * 应用模板并获得模板的内容
             */
            ob_start();
            include($template_file);
            $this->content = ob_get_contents();
            ob_end_clean();

            return $this;
        }

        public function display()
        {
            $this->render();
            echo $this->content;
        }
    }

    class TemplateException extends DumperException
    {

    }
}