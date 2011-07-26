<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

interface Controller
{
    /**
     * @abstract
     * @param $controllerPath
     * @return void
     */
    function Route($controllerPath);
}

/**
 * Controller
 * Parses the HTTP request and routes to the appropriate function
 * @package Controller
 */
class KrisController implements Controller
{
    /**
     * @var string
     */
    protected $_controllerPath = '../app/controllers/'; //with trailing slash

    /**
     * @var string
     */
    protected $_webFolder = '/'; //with trailing slash

    /**
     * @var Request
     */
    protected $_request;

    /**
     * @param string $controllerPath
     * @return void
     */
    public function Route($controllerPath)
    {
        $this->_controllerPath = $controllerPath;
        $route =  RouteRequest::CreateFromUri($_SERVER['REQUEST_URI']);
        $this->ParseRequest($route->Controller, $route->Action, $route->Params);
    }

    /**
     * @param string $controller
     * @param string $action
     * @param array $params
     * @return void
     */
    protected function ParseRequest($controller, $action, $params)
    {
        $controllerClass = ucfirst($controller) . 'Controller';

        $controllerFile = $this->_controllerPath . $controller . '/' . $controllerClass . '.php';

        $this->_request = new Request($controller, $action, $params);

        if (!preg_match('#^[a-z0-9_-]+$#i', $controller) || !file_exists($controllerFile))
        {
            $this->RequestNotFound('Controller file not found: ' . $controllerFile);
        }
        else
        {

            $function = ucfirst($action);


            if (!preg_match('#^\w[a-z0-9_-]*$#i', $function))
            {
                $this->RequestNotFound('Invalid function name: ' . $function);
            }
            else
            {
                /** @noinspection PhpIncludeInspection */
                require($controllerFile);

                if (!class_exists($controllerClass))
                {
                    $this->RequestNotFound('Controller class (' . $controllerClass . ') not found');
                }
                else
                {

                    $controller = new $controllerClass($this->_request);

                    if (!method_exists($controller, $function))
                    {
                        $this->RequestNotFound('Function not found: ' . $function . ' in controller: ' . $controllerClass);
                    }
                    else
                    {
                        $res = call_user_func_array(array($controller, $function), $params);
                        if (!is_null($res) && get_class($res) == 'RouteRequest')
                        {
                            /** @var $res RouteRequest */
                            $this->ParseRequest($res->Controller, $res->Action, $res->Params);
                        }
                    }
                }
            }
        }

    }

    /**
     * Override this function for your own custom 404 page
     *
     * @param string $msg
     * @return void
     */
    protected function RequestNotFound($msg = '')
    {
        $displayedError = false;

        if (!is_null(KrisConfig::$Error404Handler))
        {
            if (!is_array(KrisConfig::$Error404Handler))
            {
                if (function_exists(KrisConfig::$Error404Handler))
                {
                    call_user_func(KrisConfig::$Error404Handler, $msg);
                    $displayedError = true;
                }
                else
                {
                    
                    KrisConfig::LogError('Unable to call Error404Handler, function ' . KrisConfig::$Error404Handler . ' does not exist');
                }
            }
        }


        if (!$displayedError)
        {
            $this->_request->SetError(404, '<html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>' . $msg .
                    '<p>The requested URL was not found on this server.</p><p>Please go <a href="javascript: history.back(1)">back</a>' .
                    ' and try again.</p><hr /></body></html>');
        }
    }
}
