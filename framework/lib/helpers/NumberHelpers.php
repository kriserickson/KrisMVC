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
 * Number formatting functions
 */
class NumberHelpers
{
    /**
     * @static
     * @param int $bytes
     * @return string
     */
    public static function BytesToHuman($bytes)
    {
        if ($bytes > 1073741824)
        {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        else if ($bytes > 1048576)
        {
            return number_format($bytes / 1048576, 2).' MB';
        }
        else if ($bytes > 1024)
        {
            return number_format($bytes / 1024, 2).' KB';
        }
        else
        {
            return $bytes.' Bytes';
        }

    }
}

?>