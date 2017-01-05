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

        public function __construct($template_path, $template_ext = '.html')
        {
            $template_path = str_replace('\\', '/', $template_path);
            if (!is_dir($template_path)) {
                throw new TemplateException('template_path is not a valid dir');
            }
            $this->templatePath .= substr($template_path, -1) == '/' ? '' : '/';
            $this->templateExt  = $template_ext;
        }

        public function assign($name, $value)
        {
            $this->assigns[$name] = $value;
        }

        public function setTemplate($template_name)
        {
            $this->templateName = $template_name;
        }

        public function render()
        {
            $template_file = $this->templatePath . $this->templateName . $this->templateExt;
            if (!is_file($template_file)) {
                throw new TemplateException('template is not a valid file');
            }

            foreach ($assigns as $name => $value) {
                ${$name} = $value;
            }

            ob_start();
            include($template_file);

            $this->content = ob_get_contents();

            ob_end_clean();
        }

        public function display()
        {
            $this->render();
            echo $this->content;
        }
    }

    class TemplateException extends \Exception
    {

    }
}