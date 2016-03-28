<?php namespace Sutil\Http;

class Router
{
    protected $_controller = 'Index';
    protected $_action = 'index';
    protected $_args;
    protected $_file_path;
    protected $_url_base;
    protected $_url_path;
    protected $_url_controller = 'index';

    public function __construct($path)
    {
        $this->_file_path = rtrim($path, '/');
        $this->_url_base = Request::base();
        $this->_url_path = Request::path();
    }
    
    public function dispatch()
    {
        $path_arr = explode('/', strtolower($this->_url_path));
        if (!empty($path_arr[0])) {
            $this->_url_controller = $path_arr[0];
            $this->_controller = str_replace('-', '', ucwords($path_arr[0], '-'));
        }
        if (!empty($path_arr[1])) {
            $this->_action = str_replace('-', '', ucwords($path_arr[1], '-'));
        }

        // foo-bar -> FooBarController
        $controller_name =  $this->_controller.'Controller';

        // new controller instance
        $controller_file = $this->_file_path .'/'. $controller_name .'.php';
        if (!is_file($controller_file)) {
            throw new RouteException("Invalid controller {$controller_name}");
        }
        require $controller_file;
        $controller_instance = new $controller_name($this);
        $action = $this->getAction();

        // call action
        if (!method_exists($controller_instance, $action)) {
            throw new RouteException("{$action} not exists in {$controller_name}");
        }
        if (empty($this->_args)) {
            $controller_instance->$action();
        } else {
            call_user_func_array([$controller_instance, $action], $this->_args);
        }
    }


    public function getUrlPath()
    {
        return $this->_url_path;
    }

    public function getUrlBase()
    {
        return $this->_url_base;
    }

    public function getUrlController()
    {
        return $this->_url_controller;
    }

    public function getController()
    {
        return $this->_controller;
    }

    public function getAction()
    {
        return $this->_action;
    }

    public function setAction($action, $args = null)
    {
        $this->_action = $action;
        $this->_args = $args;
    }


    /**
     * link as controller/action
     * @param string $uri
     * @param null|string|array $params
     * @return string
     */
    public function getLink($uri, $params = null)
    {
        if (null !== $params) {
            $uri = (strpos($uri, '?') ? '&' : '?') . (is_array($params) ? http_build_query($params) : $params);
        }
        if (strpos($uri, '://')) {
            return $uri;
        } else {
            return Request::base() .'/'. ltrim($uri, '/');
        }
    }

    /**
     * @param $uri
     * @param null $params
     * @return string
     */
    public function getActionLink($uri = '', $params = null)
    {
        return $this->getLink($this->_url_controller .'/'. ltrim($uri), $params);
    }

    /**
     * @param $uri
     * @param null $params
     */
    public function toAction($uri = '', $params = null)
    {
        header('Location: '. self::getActionLink($uri, $params));
        exit;
    }

    /**
     * @param string $uri
     * @param null|array|string $params
     */
    public function redirect($uri, $params = null)
    {
        header('Location: '. self::getLink($uri, $params));
        exit;
    }

    /**
     * @param $uri
     * @param null $params
     */
    public function to($uri, $params = null)
    {
        $this->redirect($uri, $params);
    }
}