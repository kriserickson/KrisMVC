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
 * @package helpers
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

    /**
     * @static
     * @param string $path1
     * @param string $path2
     * @return string
     */
    public static function BuildPath($path1, $path2)
    {
        if (substr($path1, -1) != '/' && substr($path1, -1) != '\\')
        {
            $path1 .= DIRECTORY_SEPARATOR;
        }
        return $path1 . $path2;
    }

    /**
     * Recursively delete all files and directories in a folder...
     *
     * @static
     * @param $dir
     */
    public static function RecursiveDeleteDirectory($dir)
    {
        $files = scandir($dir);

        foreach ($files as $file)
        {
            if ($file != '.' && $file != '..')
            {
                $file = $dir . '/' . $file;
                if (is_dir($file))
                {
                    self::RecursiveDeleteDirectory($file);
                    rmdir($file);
                } else
                {
                    unlink($file);
                }
            }
        }
        rmdir($dir);

    }
}

