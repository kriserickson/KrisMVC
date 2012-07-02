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
     * Resizes the current image optimally from
     *
     * @param int $width
     * @param int $height
     * @return void
     */
    public function resizeToOptimal($width, $height = 0)
    {


        $image_height = $this->height();
        $image_width = $this->width();

        $multiplier = $width / $image_width;

        if ($height == 0 && $image_height > $image_height)
        {
            $height = $width;
            $width = (int)($width * ($image_width / $image_height));
        }

        if ($image_height * $multiplier <= $height || $height == 0)
        {
            $return_height = round($image_height * $multiplier);
            $return_width = round($width);
        }
        else
        {
            $multiplier = $height / $image_height;
            $return_width = round($image_width * $multiplier);
            $return_height = $height;
        }

        $new_image = imagecreatetruecolor($return_width, $return_height);
        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $return_width, $return_height, $image_width, $image_height);
        $this->image = $new_image;

    }



    /**
     * Crops an image from x,y,width, height and makes the current image that crop...
     *
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @return void
     */
    public function cropImage($x, $y, $width, $height)
    {
        $new_image = imagecreatetruecolor($width, $height);
        imagecopy($new_image, $this->image, 0, 0, $x, $y, $width, $height);
        $this->image = $new_image;
    }

    /**
     * returns the width of an image
     * @return int
     */
    public function width()
    {
        return imagesx($this->image);
    }

    /**
     * returns the height of an image
     * @return int
     */
    public function height()
    {
        return imagesy($this->image);
    }


}

