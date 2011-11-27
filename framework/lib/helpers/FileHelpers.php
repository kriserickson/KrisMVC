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
 * Static file helpers...
 */
class FileHelpers
{
    /**
     * Ensures a directory exists, recursively creating them if possible
     *
     * @static
     * @param string $directory
     * @return bool
     */
    public static function EnsureDirectoryExists($directory)
    {
        if (is_dir($directory))
        {
            return true;
        }
        return mkdir($directory, 777, true);
    }

    public static function BuildPath($path1, $path2)
    {
        if (substr($path1,-1) != '/' && substr($path1,-1) != '\\')
        {
            $path1 .= DIRECTORY_SEPARATOR;
        }
        return $path1.$path2;
    }
}

?>