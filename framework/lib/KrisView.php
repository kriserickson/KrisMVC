<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

//===============================================================
// View
// For plain .php templates
//===============================================================
class KrisView
{
    protected $_file = '';
    protected $_vars = array();

    function __construct($file = '', $vars = '')
    {
        if ($file)
        {
            $this->_file = $file;
        }
        if (is_array($vars))
        {
            $this->_vars = $vars;
        }
        return $this;
    }

    function __set($key, $var)
    {
        return $this->set($key, $var);
    }

    function set($key, $var)
    {
        $this->_vars[$key] = $var;
        return $this;
    }

    //for adding to an array
    function add($key, $var)
    {
        $this->_vars[$key][] = $var;
    }

    function fetch($file = '', $vars = '', $merge = true)
    {
        if ($merge)
        {
            if (is_array($vars))
            {
                $this->_vars = array_merge($this->_vars, $vars);
            }
            extract($this->_vars);
        }
        else
        {
            extract($vars);
        }

        if (strlen($file) == 0)
        {
            $file = $this->_file;
        }
        ob_start();
        require($file);
        return ob_get_clean();
    }

    function dump($vars = '')
    {
        if (is_array($vars))
        {
            $this->_vars = array_merge($this->_vars, $vars);
        }
        extract($this->_vars);
        require($this->_file);
    }

}

 
