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
 * Image resizing class...
 */
class ImageResizer
{
    /**
     * @var resource
     */
    private $image;

    /**
     * @var int
     */
    private $image_type;

    /**
     * @param $filename
     */
    function __construct($filename)
    {

        $image_info = getimagesize($filename);
        $this->image_type = $image_info[2];
        if ($this->image_type == IMAGETYPE_JPEG)
        {

            $this->image = imagecreatefromjpeg($filename);
        }
        elseif ($this->image_type == IMAGETYPE_GIF)
        {

            $this->image = imagecreatefromgif($filename);
        }
        elseif ($this->image_type == IMAGETYPE_PNG)
        {

            $this->image = imagecreatefrompng($filename);
        }
        else
        {
            throw new Exception('Unsupported image type.  Must be jpg, gif or png');
        }
    }

    /**
     * @param $filename
     * @param int $image_type
     * @param int $compression
     * @return bool
     */
    function save($filename, $image_type = null, $compression = 50)
    {
        if ($image_type == null)
        {
            $image_type = $this->image_type;
        }

        if ($image_type == IMAGETYPE_JPEG)
        {
            return imagejpeg($this->image, $filename, $compression);
        }
        elseif ($image_type == IMAGETYPE_GIF)
        {
            return imagegif($this->image, $filename);
        }
        elseif ($image_type == IMAGETYPE_PNG)
        {

            return imagepng($this->image, $filename);
        }
        else
        {
            throw new Exception('Unsupported image save type.  Must be jpg, gif or png');
        }

    }

    /**
     * @param $width
     * @param $height
     * @return void
     */
    function resizeToOptimal($width, $height)
    {
        $image_height = imagesy($this->image);
        $image_width = imagesx($this->image);

        if ($image_height > $height && $height > $width)
        {
            $ratio = $height / $image_height;
            $width = $image_width * $ratio;

        }
        else
        {
            $ratio = $width / $image_width;
            $height = $image_height * $ratio;

        }

        $new_image = imagecreatetruecolor($width, $height);
        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $image_width, $image_height);
        $this->image = $new_image;
    }

}

?>