<?php namespace Sutil\View;

class Compiler
{
    protected $_view_path = '';
    protected $_compiled_path = '';

    public function __construct(View $view)
    {
        $this->_view_path = $view->getViewPath();
        $this->_compiled_path = $view->getCompiledPath();

        if (!is_dir($this->_compiled_path)) {
            if (!mkdir($this->_compiled_path, 0766)) {
                throw new \Exception('Unable to create the view compiled directory');
            }
        } elseif (!is_writable($this->_compiled_path)) {
            throw new \Exception('The view compiled directory is unwritable');
        }
    }

    /**
     * Get view file
     */
    public function getViewFile($template)
    {
        return "{$this->_view_path}/{$template}.php";
    }


    /**
     * Compile the template
     * @param $template
     * @return void
     */
    public function compile($template)
    {
        $compiled_file = "{$this->_compiled_path}/{$template}.php";
        $compiled_dir = dirname($compiled_file);
        if (!is_dir($compiled_dir)) {
            mkdir($compiled_dir, 0766, true);
        }
        file_put_contents($compiled_file, $this->_parse($template));
    }


    public function _parse($template)
    {
        $view_file = $this->getViewFile($template);
        if (!is_file($view_file)) {
            throw new \Exception("View template is not exists: {$template}");
        }

        $self = $this;
        return preg_replace_callback('/\{\{\s*(\w+)\s+([\w\/\.]+)\s*\}\}/', function($matches) use($self){
            $method = '_parse'. ucfirst(strtolower($matches[1]));
            if (!method_exists($self, $method)) {
                return $matches[0];
            }
            return $self->$method($matches[2]);
        }, file_get_contents($view_file));
    }


    protected function _parseInclude($var)
    {
        return $this->_parse($var);
    }

    protected function _parseTrans($var)
    {
        return gettext($var);
    }
}