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
     * @param string $controllerPath
     * @return void
     */
    function Route($controllerPath);

    /**
     * @abstract
     * @param string $originalUrl
     * @param string $reroutedUrl
     * @return void
     */
    function ReRoute($originalUrl, $reroutedUrl);
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
     * @var
     */
    protected  $_reroute = array();

    /**
     * @param string $controllerPath
     * @return void
     */
    public function Route($controllerPath)
    {
        $this->_controllerPath = $controllerPath;
        $route =  RouteRequest::CreateFromUri($this->GetRequestUri());
        $this->ParseRequest($route->Controller, $route->Action, $route->Params);
    }

    protected function GetRequestUri()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        if (strlen(KrisConfig::WEB_FOLDER) == 0 || strpos($requestUri, KrisConfig::WEB_FOLDER) === 0)
        {
            $requestUri = substr($requestUri, strlen(KrisConfig::WEB_FOLDER . '/'));
        }
        foreach ($this->_reroute as $originalRoute => $reRoute)
        {
            if (preg_match('/^'.str_replace('/','\\/',$originalRoute).'$/', $requestUri))
            {
                $requestUri = preg_replace('/'.str_replace('/','\\/',$originalRoute).'/', $reRoute, $requestUri);
                break;
            }
        }
        return $requestUri;
    }

    public function ReRoute($originalUrl, $reroutedUrl)
    {
        $this->_reroute[$originalUrl] = $reroutedUrl;
    }

    /**
     * @param string $controller
     * @param string $action
     * @param array $params
     * @return void
     */
    protected function ParseRequest($controller, $action, $params)
    {
        $this->_request = new Request($controller, $action, $params);

        if ($this->GetControllerRequest($controller, $action, $error, $function, $controllerObj))
        {
            $res = call_user_func_array(array($controllerObj, $function), $params);
            if (!is_null($res) && get_class($res) == 'RouteRequest')
            {
                /** @var $res RouteRequest */
                $this->ParseRequest($res->Controller, $res->Action, $res->Params);
            }
        }
        else
        {
            $this->RequestNotFound($error);
        }

    }

    protected function GetControllerRequest($controller, $action, &$error, &$function, &$controllerObj)
    {
        $controllerClass = ucfirst($controller) . 'Controller';

        $controllerFile = $this->_controllerPath . $controller . '/' . $controllerClass . '.php';

        if (!preg_match('#^[a-z0-9_-]+$#i', $controller) || !file_exists($controllerFile))
        {
            $error = 'Controller file not found: ' . $controllerFile;
        }
        else
        {

            $function = ucfirst($action);


            if (!preg_match('/^\w[a-z0-9_-]*$/i', $function))
            {
                $error = 'Invalid function name: ' . $function;
            }
            else
            {
                /** @noinspection PhpIncludeInspection */
                require($controllerFile);

                if (!class_exists($controllerClass))
                {
                    $error = 'Controller class (' . $controllerClass . ') not found';
                }
                else
                {

                    $controllerObj = new $controllerClass($this->_request);

                    if (!method_exists($controllerObj, $function))
                    {
                        $error = 'Function not found: ' . $function . ' in controller: ' . $controllerClass;
                    }
                    else
                    {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Override this function for your own custom 404 page
     *
     * @param string $msg
     * @return void
     */
    protected function RequestNotFound($msg)
    {
        $displayedError = false;

        if (!is_null(KrisConfig::$Error404Handler) && is_array(KrisConfig::$Error404Handler) && count(KrisConfig::$Error404Handler) > 1)
        {
            if ($this->GetControllerRequest(KrisConfig::$Error404Handler['controller'], KrisConfig::$Error404Handler['action'], $error, $function, $controllerObj))
            {
                call_user_func_array(array($controllerObj, $function), array($msg));
                $displayedError = true;
            }
            else
            {
                KrisConfig::LogError('Unable to call Error404Handler, function ' . KrisConfig::$Error404Handler . ' does not exist');
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
