<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require_once 'Mustache.php';

/**
 * View Class that uses Moustache Templating engine instead of PHP.
 */
class MustacheView extends KrisView
{
    /**
     * @param string $file
     * @param string|array $vars
     * @param bool $merge
     * @return string
     */
    public function fetch($file = '', $vars = array(), $merge = true)
    {
        if ($merge)
        {
            $vars = array_merge($this->_vars, $vars);
        }

        if (strlen($file) == 0)
        {
            $file = $this->_file;
        }


        $m = new Mustache();
        return $m->render(file_get_contents($file, true), $vars);

    }

    /**
     * @param array|null $vars
     * @return void
     */
    public function dump($vars = null)
    {
        echo $this->contents($vars);
    }

    /**
     * @param array|null $vars
     * @return string
     */
    public function contents($vars = null)
    {
        if (is_array($vars))
        {
            $this->_vars = array_merge($this->_vars, $vars);
        }

        $m = new Mustache();

        return $m->render(file_get_contents($this->_file, true), $this->_vars);
    }


}
