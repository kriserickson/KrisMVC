<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Controller
 * Parses the HTTP request and routes to the appropriate function
 * @package Controller
 */
class KrisController
{
    protected $_controllerPath = '../app/controllers/'; //with trailing slash
    protected $_webFolder = '/'; //with trailing slash
    protected $_requestUriParts = array();
    protected $_controller;
    protected $_action;
    protected $_params = array();

    /**
     * @param string $controller_path
     * @param string $web_folder
     * @param string $default_controller
     * @param string $default_action
     */
    function __construct($controller_path, $web_folder, $default_controller, $default_action)
    {
        $this->_controllerPath = $controller_path;
        $this->_webFolder = $web_folder;
        $this->_controller = $default_controller;
        $this->_action = $default_action;
        $this->explode_http_request()->parse_http_request()->route_request();
    }


    /**
     * Converts the http request into its URI parts
     *
     * @return KrisController
     */
    protected  function explode_http_request()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        if (strpos($requestUri, $this->_webFolder) === 0)
        {
            $requestUri = substr($requestUri, strlen($this->_webFolder.'/'));
        }
        $this->_requestUriParts = $requestUri ? explode('/', $requestUri) : array();
        return $this;
    }

    /**
     * This function parses the HTTP request to get the controller name, action name and parameter array.
     *
     * @return KrisController
     */
    protected function parse_http_request()
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
            $this->_params = array();
            foreach( array_slice($p, 2) as $array_item)
            {
                $this->_params[] = urldecode($array_item);
            }

        }
        return $this;
    }


    /**
     * This function maps the controller name and action name to the file location of the .php file to include
     *
     * All Controllers have to be named XXXController where XXX is the name of controller in the url.
     * For example http://localhost/main/hello would load the MainController class from the MainController.php
     * file and call the Index function on that MainController class.  Function names must start with a word
     * and only contain letters,numbers and the underscore character.
     *
     *
     * @return KrisController
     */
    protected function route_request()
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

        /** @noinspection PhpIncludeInspection */
        require($controllerFile);

        if (!class_exists($controllerClass))
        {
            $this->request_not_found('Controller class ('.$controllerClass.') not found');
        }

        $controller = new $controllerClass($this->_action, $this->_params);

        if (!method_exists($controller, $function))
        {
            $this->request_not_found('Function not found: ' . $function);
        }

        call_user_func_array(array($controller, $function), $this->_params);

        return $this;
    }

    /**
     * Override this function for your own custom 404 page
     *
     * @param string $msg
     * @return void
     */
    protected function request_not_found($msg = '')
    {
        $displayedError = false;

        if (!is_null(KrisConfig::$Error404Handler))
        {
            if (!is_array(KrisConfig::$Error404Handler))
            {
                if (function_exists(KrisConfig::$Error404Handler))
                {
                    call_user_func(KrisConfig::$Error404Handler, $msg);
                }
                else
                {
                    KrisConfig::LogError('Unable to call Error404Handler, function '.KrisConfig::$Error404Handler.' does not exist');
                }
            }
        }



        if (!$displayedError)
        {
            header("HTTP/1.0 404 Not Found");
            die('<html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>' . $msg .
                '<p>The requested URL was not found on this server.</p><p>Please go <a href="javascript: history.back(1)">back</a>'.
                ' and try again.</p><hr /></body></html>');
        }
    }
}
