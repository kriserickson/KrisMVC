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
 * Static methods used for Html Generation
 */
class HtmlHelpers
{

    /**
     * @static
     * @param string $id
     * @param string $innerHtml
     * @param string $class
     * @param string $name
     * @param string $style
     * @return string
     */
    public static function CreateButton($id, $innerHtml, $class = '', $name = '', $style = '')
    {
        if (strlen($name) == 0)
        {
            $name = $id;
        }
        if (strlen($class) > 0)
        {
            $class = ' class="'.$class.'"';
        }
        if (strlen($style) > 0)
        {
            $style = ' style="'.$style.'"';
        }

        return '<button id="'.$id.'" name="'.$name.'"'.$class.$style.'>'.$innerHtml.'</button>';
    }

    /**
     * @static
     * @param string $id
     * @param string $class
     * @param string $content
     * @param string $style
     * @return string
     */
    public static function CreateUnorderedListItem($id, $class, $content,$style='')
    {
        if (strlen($id) > 0)
        {
            $id = ' id="'.$id.'"';
        }
        if (strlen($class) > 0)
        {
            $class = ' class="'.$class.'"';
        }
        if (strlen($style) > 0)
        {
            $style = ' style="'.$style.'"';
        }
        return '<li'.$id.$class.$style.'>'.$content.'</li>';
    }

    /**
     * @static
     * @param string $id
     * @param string $class
     * @param string $href
     * @param string $content
     * @param string $style
     * @return string
     */
    public static function CreateLink($id, $class, $href, $content, $style = '')
    {
        if (strlen($id) > 0)
        {
            $id = ' id="'.$id.'"';
        }
        if (strlen($class) > 0)
        {
            $class = ' class="'.$class.'"';
        }
        if (strlen($style) > 0)
        {
            $style = ' style="'.$style.'"';
        }
        return '<a'.$id.$class.$style.' href="'.$href.'">'.$content.'</a>';
    }

    /**
     * Create an HTML select...
     *
     * @param string $id
     * @param array $values
     * @param string $defaultValue
     * @param string $name
     * @param string $class
     * @return string
     */
    public static function CreateSelect($id, $values, $defaultValue, $name = '', $class = '')
    {
        $class = (strlen($class) > 0) ? ' class="'.$class.'"' : '';
        $name = (strlen($name) == 0) ? $id : $name;
        $select =  '<select name="' . $name . '" id="' . $id . '"' . $class . '>' . PHP_EOL;
        foreach ($values as $value => $display)
        {
            $select .= '<option value="' . $value . '"' . ($defaultValue == $value ? ' selected="selected"'
                    : '') . '>' . $display . '</option>.PHP_EOL';
        }
        return $select . '</select>' . PHP_EOL;
    }


    /**
     * Generates a text input...
     *
     * @param string $id
     * @param string $value
     * @param string $name
     * @param string $class
     * @param string $type
     * @return string
     */
    public static function CreateInput($id, $value, $name = '', $class = '', $type = 'text')
    {
        $name = (strlen($name) == 0) ? $id : $name;
        return  '<input type="' . $type . '" name="' . $name . '" id="' . $id . '" class="' . $class . '" value="' . $value . '"/>';
    }

    /**
     * @param string $id
     * @param string $value
     * @param string $name
     * @param string $class
     * @param int $rows
     * @param int $cols
     * @return string
     */
    public static function CreateTextarea($id, $value, $name = '', $class = '', $rows = 20, $cols = 100)
    {
        $name = (strlen($name) == 0) ? $id : $name;
        return '<textarea name="' . $name . '" id="' . $id . '"'.(strlen($class) > 0 ? ' class="' . $class . '"' : '').
                ' rows="'.$rows.'" cols="'.$cols.'">' . $value . '</textarea>';
    }

    /**
     * @static
     * @param string $type
     * @param string $content
     * @return string
     */
    public static function CreateScript($type, $content)
    {
        return '<script'.(strlen($type) > 0 ? ' type="'.$type.'"' : '').'>'.PHP_EOL.$content.PHP_EOL.'</script>';
    }

    /**
     * @static
     * @param string $id
     * @param string $src
     * @param string $dir
     * @param string $class
     * @return string
     */
    public static function CreateImage($id, $src, $dir, $class = '')
    {
        return (strlen($src) > 0) ? '<img id="'.$id.'"'.(strlen($class) > 0 ? ' class="' . $class . '"' : '').' src="'.KrisConfig::WEB_FOLDER.$dir.'/'.$src.'"/>' : '';
    }

    /**
     * @static
     * @param string $id
     * @param string $name
     * @param string $class
     * @param int $max_file_size
     * @return string
     */
    public static function CreateFileInput($id, $name = '', $class = '', $max_file_size = 100000)
    {
        $name = (strlen($name) == 0) ? $id : $name;
        return '<input type="hidden" name="MAX_FILE_SIZE" value="'.$max_file_size.'" />'.
                '<input type="file" name="'.$name.'" id="'.$id.'"'.(strlen($class) > 0 ? ' class="' . $class . '"' : '').'/>';
    }

    /**
     * @static
     * @param string|array $cssArray
     */
    public static function DisplayCssFiles($cssArray)
    {
        if (!is_array($cssArray))
        {
            $cssArray = array($cssArray);
        }
        foreach ($cssArray as $css)
        {
            echo '<link rel="stylesheet" href="'.KrisConfig::WEB_FOLDER.'/css/'.$css.'.css" type="text/css" media="screen" />';
        }
    }

    /**
     * We should think about creating a UrlHelpers class
     *
     * @static
     * @param string $url
     * @return string
     */
    public static function StripSlash($url)
    {
        if (substr($url,-1) == '/')
        {
            return substr($url, 0, -1);
        }
        return $url;
    }
}
