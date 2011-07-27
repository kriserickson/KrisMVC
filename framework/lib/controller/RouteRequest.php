<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
class RouteRequest
{
    public $Controller;
    public $Action;
    public $Params;

    /**
     * @param string $controller
     * @param string|null $action
     * @param array|null $params
     * @return \RouteRequest
     *
     */
    public function __construct($controller = '', $action = '', $params = null)
    {
        $this->Controller = $controller;
        $this->Action = $action;
        $this->Params = is_array($params) ? $params : array();
    }

    /**
     * This function parses the HTTP request to get the controller name, action name and parameter array.
     * @param $requestUri
     *
     */
    protected function ParseHttpRequest($requestUri)
    {
        $this->Params = array();

        $parts = $requestUri ? explode('/', $requestUri) : array();

        if (isset($parts[0]) && $parts[0])
        {
            $this->Controller = $parts[0];
        }
        if (isset($parts[1]) && $parts[1])
        {
            $this->Action = $parts[1];
        }
        if (isset($parts[2]))
        {
            foreach (array_slice($parts, 2) as $array_item)
            {
                $this->Params[] = urldecode($array_item);
            }

        }

    }

    /**
     * This function maps the controller name and action name to the file location of the .php file to include
     *
     * All Controllers have to be named XXXController where XXX is the name of controller in the url.
     * For example http://localhost/main/hello would load the MainController class from the MainController.php
     * file and call the Index function on that MainController class.  Function names must start with a word
     * and only contain letters,numbers and the underscore character.
     *
     * @static
     * @param string $requestUri
     * @return RouteRequest
     */
    public static function CreateFromUri($requestUri)
    {
        if (strlen(KrisConfig::WEB_FOLDER) == 0 || strpos($requestUri, KrisConfig::WEB_FOLDER) === 0)
        {
            $requestUri = substr($requestUri, strlen(KrisConfig::WEB_FOLDER . '/'));
        }
        $route = new RouteRequest();
        $route->Controller = KrisConfig::DEFAULT_CONTROLLER;
        $route->Action = KrisConfig::DEFAULT_ACTION;
        $route->ParseHttpRequest($requestUri);
        return $route;
    }

}