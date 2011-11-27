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
    /**
     * @static
     * @param string $path1
     * @param string $path2
     * @param bool $forceUnix
     * @return string
     */
    public static function BuildPath($path1, $path2, $forceUnix = false)
    {
        $dirSep = $forceUnix ? '/' : DIRECTORY_SEPARATOR;
        if (substr($path1,-1) != DIRECTORY_SEPARATOR && substr($path2,0,1) != DIRECTORY_SEPARATOR)
        {
            $path1 .= $dirSep;
        }
        return $path1.$path2;
    }

    /**
     * @static
     * @param string $path
     * @return string
     */
    public static function UnixifyPath($path)
    {
        // Converts paths like '\tmp\dir' to '/tmp/dir' and '//tmp//dir' to '/tmp/dir'
        return preg_replace('|[\\\/]+|', '/', $path);
    }
}