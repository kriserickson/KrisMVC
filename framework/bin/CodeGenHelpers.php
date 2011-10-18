<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

class CodeGenHelpers
{
    public static function BuildPath($path1, $path2)
    {
        if (substr($path1,-1) != '/' && substr($path1,-1) != '\\')
        {
            $path1 .= DIRECTORY_SEPARATOR;
        }
        return $path1.$path2;
    }
}