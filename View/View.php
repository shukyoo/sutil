<?php namespace Sutil\View;
/**
 * 开发模式：每次运行都是重新编译，编译后的文件存在于版本库里
 * 生产模式：不检查，直接使用编译后的文件（除非编译文件不存在）
 * . 编译：多碎片合成
 * . 多语言模式下的语言变量替换，需要编译保存为不同语言下的模板文件
 * . 资源的管理（这个是另外的功能）
 * . 自定义html宏（这个是html部分另外的功能）
 *
 * strtr($message, $replace);
 * test{hello}aaa{vvv}aaa     ['{hello}' => 'aaaaa', '{vvv}' => 'bbbbb']
 */
class View
{
    protected $_view_path = '';

    // If true, it will always recompile，recommend "true" for development, "false" for production.
    protected $_recompile = false;

    protected $_locale = null;

    public function __construct($view_path, $options = [])
    {
        $this->_view_path = rtrim($view_path, '/');

        if (isset($options['recompile'])) {
            $this->_recompile = (bool)$options['recompile'];
        }

        if (isset($options['locale'])) {
            $this->_locale = strtolower($options['locale']);
        }
    }


    public function template($template)
    {
        $compiled_file = $this->getCompiledPath() ."/{$template}.php";

        if (!is_file($compiled_file) || $this->_recompile) {
            $compiler = new Compiler($this);
            $compiler->compile($template);
        }

        return $compiled_file;
    }


    public function getViewPath()
    {
        return $this->_view_path;
    }

    public function getCompiledPath()
    {
        $loc = $this->_locale ? "/{$this->_locale}" : '';
        return "{$this->_view_path}/_compiled{$loc}";
    }

}

