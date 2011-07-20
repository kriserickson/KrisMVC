<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
abstract class DefaultController
{

    /**
     * @var KrisView
     */
    protected $_view;

    /**
     * @var string
     */
    protected $_error = '';

    /**
     * @var Auth
     */
    protected $_auth;


    /**
     * @return null|RouteRequest
     */
    abstract function Index();


        /**
     * Returns an html appropriate error string...
     *
     * @return string
     */
    protected function GetHtmlError()
    {
        return nl2br($this->_error);
    }

    /**
     * Adds an error to the internal error for the class.  Concatenates a line break if an error already exists.
     *
     * @param $error
     * @return void
     */
    protected function AddError($error)
    {
        $this->_error .= (strlen($this->_error) > 0 ? PHP_EOL : '').$error;
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
        return strlen($this->_error) > 0;
    }
}
