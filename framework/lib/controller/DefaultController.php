<?php
/**
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
abstract class DefaultController
{

    /**
     * @var KrisView
     */
    protected $view;

    /**
     * @var string
     */
    protected $error = '';

    /**
     * @var Auth
     */
    protected $auth;

    /**
     * @var Request
     */
    protected $request;


    /**
     * Returns an html appropriate error string...
     *
     * @return string
     */
    protected function GetHtmlError()
    {
        return nl2br($this->error);
    }

    /**
     * Adds an error to the internal error for the class.  Concatenates a line break if an error already exists.
     *
     * @param $error
     * @return void
     */
    protected function AddError($error)
    {
        $this->error .= (strlen($this->error) > 0 ? PHP_EOL : '').$error;
    }

    /**
     * @return null|RouteRequest
     */
    protected function CanView()
    {
        return null;
    }

    /**
     * Has an error occurred yet?
     *
     * @return bool
     */
    protected  function HasError()
    {
        return strlen($this->error) > 0;
    }
}
