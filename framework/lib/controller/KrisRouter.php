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
 * @package controller
 */
interface Router
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

    /**
     * @abstract
     * @param string $route
     * @return void
     */
    function SkipActionForRoute($route);
}

/**
 * Controller
 * Parses the HTTP request and routes to the appropriate function
 * @package controller
 */
class KrisRouter implements Router
{
    /**
     * @var string
     */
    protected $controllerPath = '../app/controllers/'; //with trailing slash

    /**
     * @var string
     */
    protected $webFolder = '/'; //with trailing slash

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var
     */
    protected  $reroute = array();

    /**
     * @var
     */
    protected  $routeActionOnly = array();

    /**
     * @param string $controllerPath
     * @return void
     */
    public function Route($controllerPath)
    {
        $this->controllerPath = $controllerPath;
        $route =  RouteRequest::CreateFromUri($this->GetRequestUri(), $this->routeActionOnly);
        try
        {
            $this->ParseRequest($route->Controller, $route->Action, $route->Params);
        }
        catch (Exception $ex)
        {
            /** @var $log Log */
            $log = AutoLoader::Container()->get('Log');
            $trace = $ex->getTrace();
            $message = '';
            foreach ($trace as $line)
            {
                $message .= '   '.(isset($line['file']) ? 'file: '.$line['file'].', ' : 'Anonymous: ').(isset($line['line']) ? ' line: '.$line['line'].' : ' : '').
                        (isset($line['class']) ? $line['class'].'->' : '').
                        $line['function'].'('.implode(',', array_map(create_function('$a', 'return gettype($a);'), $line['args'])).')'.PHP_EOL;
            }
            $log->Error('Uncaught exception: '.$ex->getMessage().PHP_EOL.$message);

            if ($this->request->IsJson)
            {
                $this->request->JsonResponse(array('success' => false, 'message' => 'An error occurred, please try again later'));
            }
            else
            {
                $route = RouteRequest::GetErrorRequest(500, 'An unknown error has occurred.  Please try again later.');
                $this->ParseRequest($route->Controller, $route->Action, $route->Params);
            }
        }

    }

    /**
     * @return mixed|string
     */
    protected function GetRequestUri()
    {
        $requestUri = $_SERVER['REQUEST_URI'];

        if (strlen(KrisConfig::WEB_FOLDER) == 0 || strpos($requestUri, KrisConfig::WEB_FOLDER) === 0)
        {
            $webFolder = KrisConfig::WEB_FOLDER;
            if (substr($webFolder,-1) != '/')
            {
                $webFolder .= '/';
            }
            $requestUri = substr($requestUri, strlen($webFolder));
        }
        foreach ($this->reroute as $originalRoute => $reRoute)
        {
            if (preg_match('/^'.str_replace('/','\\/',$originalRoute).'$/', $requestUri))
            {
                $requestUri = preg_replace('/'.str_replace('/','\\/',$originalRoute).'/', $reRoute, $requestUri);
                break;
            }
        }
        return $requestUri;
    }

    /**
     * @param string $originalUrl - route regex
     * @param string $reroutedUrl - reroute address
     * @return void
     */
    public function ReRoute($originalUrl, $reroutedUrl)
    {
        $this->reroute[$originalUrl] = $reroutedUrl;
    }

    /**
     * @param string $controller
     * @param string $action
     * @param array $params
     * @return void
     */
    protected function ParseRequest($controller, $action, $params)
    {
        $this->request = new Request($controller, $action, $params);

        $error = '';

        if ($this->GetControllerRequest($controller, $this->request->Action(), $error, $controllerObj, $function))
        {
            $res = call_user_func_array(array($controllerObj, $function), $this->request->Params());
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

    /**
     * @param string $controller
     * @param string $action
     * @param string $error
     * @param object $controllerObj
     * @param string $function
     *
     * @return bool
     */
    protected function GetControllerRequest($controller, $action, &$error, &$controllerObj, &$function)
    {
        $controllerClass = ucfirst($controller) . 'Controller';

        $controllerFile = $this->controllerPath . $controller . '/' . $controllerClass . '.php';



        if (!preg_match('#^[a-z0-9_-]+$#i', $controller) || !file_exists($controllerFile))
        {
            $action = $controller;
            $controller = KrisConfig::DEFAULT_CONTROLLER;
            $controllerClass = ucfirst($controller) . 'Controller';
            $controllerFile = $this->controllerPath . $controller . '/' . $controllerClass . '.php';
        }

        $function = ucfirst($action);

        if (!preg_match('/^\w[a-z0-9_-]*$/i', $function))
        {
            $error = 'Invalid function name: ' . $function;
        }
        else
        {
            /** @noinspection PhpIncludeInspection */
            require_once($controllerFile);

            if (!class_exists($controllerClass))
            {
                $error = 'Controller class (' . $controllerClass . ') not found';
            }
            else
            {

                $controllerObj = new $controllerClass($this->request);

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
            if ($this->GetControllerRequest(KrisConfig::$Error404Handler['controller'], KrisConfig::$Error404Handler['action'], $error, $controllerObj, $function))
            {
                if (!KrisConfig::DEBUG)
                {
                    $msg = '';
                }
                call_user_func_array(array($controllerObj, $function), array($msg));
                $displayedError = true;
            }
            else
            {
                /** @var $log Log */
                $log = AutoLoader::Container()->get('Log');
                $log->Error('Unable to call Error404Handler, function ' . KrisConfig::$Error404Handler . ' does not exist');
            }
        }

        if (!$displayedError)
        {
            $this->request->SetError(404, '<html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>' . $msg .
                    '<p>The requested URL was not found on this server.</p><p>Please go <a href="javascript: history.back(1)">back</a>' .
                    ' and try again.</p><hr /></body></html>');
        }
    }


    /**
     * @param string $route
     * @return void
     */
    function SkipActionForRoute($route)
    {
        $this->routeActionOnly[$route] = true;
    }
}
