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
     * @param string $action
     * @param array $params
     */
    public function __construct($controller, $action, $params)
    {
        $this->Controller = $controller;
        $this->Action = $action;
        $this->Params = $params;
    }

}