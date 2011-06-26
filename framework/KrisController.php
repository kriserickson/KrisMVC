<?php

//===============================================================
// Controller
// Parses the HTTP request and routes to the appropriate function
//===============================================================
class KrisController
{
    protected $_controllerPath = '../app/controllers/'; //with trailing slash
    protected $_webFolder = '/'; //with trailing slash
    protected $_requestUriParts = array();
    protected $_controller;
    protected $_action;
    protected $_params = array();

    function __construct($controller_path, $web_folder, $default_controller, $default_action)
    {
        $this->_controllerPath = $controller_path;
        $this->_webFolder = $web_folder;
        $this->_controller = $default_controller;
        $this->_action = $default_action;
        $this->explode_http_request()->parse_http_request()->route_request();
    }

    function explode_http_request()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        if (strpos($requestUri, $this->_webFolder) === 0)
        {
            $requestUri = substr($requestUri, strlen($this->_webFolder));
        }
        $this->_requestUriParts = $requestUri ? explode('/', $requestUri) : array();
        return $this;
    }

    //This function parses the HTTP request to get the controller name, action name and parameter array.
    function parse_http_request()
    {
        $this->_params = array();
        $p = $this->_requestUriParts;
        if (isset($p[0]) && $p[0])
        {
            $this->_controller = $p[0];
        }
        if (isset($p[1]) && $p[1])
        {
            $this->_action = $p[1];
        }
        if (isset($p[2]))
        {
            $this->_params = array_slice($p, 2);
        }
        return $this;
    }

    //This function maps the controller name and action name to the file location of the .php file to include
    function route_request()
    {
        $controllerClass = ucfirst($this->_controller). 'Controller';

        $controllerFile = $this->_controllerPath . $this->_controller . '/' . $controllerClass.'.php';

        if (!preg_match('#^[a-z0-9_-]+$#i', $this->_controller) || !file_exists($controllerFile))
        {
            $this->request_not_found('Controller file not found: ' . $controllerFile);
        }

        $function = ucfirst($this->_action);

        if (!preg_match('#^\w[a-z0-9_-]*$#i', $function))
        {
            $this->request_not_found('Invalid function name: ' . $function);
        }

        include($controllerFile);

        if (!class_exists($controllerClass))
        {
            $this->request_not_found('Controller class ('.$controllerClass.') not found');
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $function))
        {
            $this->request_not_found('Function not found: ' . $function);
        }

        call_user_func_array(array($controller, $function), $this->_params);

        return $this;
    }

    //Override this function for your own custom 404 page
    function request_not_found($msg = '')
    {
        header("HTTP/1.0 404 Not Found");
        die('<html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>' . $msg .
                '<p>The requested URL was not found on this server.</p><p>Please go <a href="javascript: history.back(1)">back</a>'.
                ' and try again.</p><hr /><p>Powered By: <a href="http://kissmvc.com">KISSMVC</a></p></body></html>');
    }
}
