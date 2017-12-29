<?php

namespace Phalcon\Image;

use Phalcon\Image;

/**
 * Phalcon\Image\Adapter
 *
 * All image adapters must use this class
 */
abstract class Adapter implements AdapterInterface
{
    protected $_file;

    protected $_image;


    protected $_realpath;


    protected $_width;


    protected $_height;


    protected $_type;


    protected $_mime;

    protected static $_checked = false;


    public function getImage()
    {
        return $this->_image;
    }

    public function getRealpath()
    {
        return $this->_realpath;
    }

    public function getWidth()
    {
        return $this->_width;
    }

    public function getHeight()
    {
        return $this->_height;
    }

    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param int|null $width
     * @param int|null $height
     * @param int $master
     * @throws Exception
     * @return Adapter
     */
    public function resize($width = null, $height = null, $master = Image::AUTO)
    {
        if ((!is_int($width) && !is_null($width)) ||
            (!is_int($height) && !is_null($height)) ||
            !is_int($master)) {
            throw new Exception('Invalid parameter type.');
        }

        if ($master == Image::TENSILE) {

            if (!$width || !$height) {
                throw new Exception("width and height must be specified");
            }

        } else {

            if ($master == Image::AUTO) {

                if (!$width || !$height) {
                    throw new Exception("width and height must be specified");
                }

                $master = ($this->_width / $width) > ($this->_height / $height) ? Image::WIDTH : Image::HEIGHT;
            }

            if ($master == Image::INVERSE) {

                if (!$width || !$height) {
                    throw new Exception("width and height must be specified");
                }

                $master = ($this->_width / $width) > ($this->_height / $height) ? Image::HEIGHT : Image::WIDTH;
            }

            switch ($master) {

                case Image::WIDTH:
                    if (!$width) {
                        throw new Exception("width must be specified");
                    }
                    $height = $this->_height * $width / $this->_width;
                    break;

                case Image::HEIGHT:
                    if (!$height) {
                        throw new Exception("height must be specified");
                    }
                    $width = $this->_width * $height / $this->_height;
                    break;

                case Image::PRECISE:
                    if (!$width || !$height) {
                        throw new Exception("width and height must be specified");
                    }
                    $ratio = $this->_width / $this->_height;

                    if (($width / $height) > $ratio) {
                        $height = $this->_height * $width / $this->_width;
                    } else {
                        $width = $this->_width * $height / $this->_height;
                    }
                    break;

                case Image::NONE:
                    if (!$width) {
                        $width = (int)$this->_width;
                    }

                    if (!$height) {
                        $width = (int)$this->_height;
                    }
                    break;
            }
        }

        $width = (int)max(round($width), 1);
        $height = (int)max(round($height), 1);

        $this->{"_resize"}($width, $height);

        return $this;
    }

    /**
     * This method scales the images using liquid rescaling method. Only support Imagick
     *
     * @param int $width new width
     * @param int $height new height
     * @param int $deltaX How much the seam can traverse on x-axis. Passing 0 causes the seams to be straight.
     * @param int $rigidity Introduces a bias for non-straight seams. This parameter is typically 0.
     * @return Adapter
     */
    public function liquidRescale($width, $height, $deltaX = 0, $rigidity = 0)
    {
        $width = (int)$width;
        $height = (int)$height;
        $deltaX = (int)$deltaX;
        $rigidity = (int)$rigidity;
        $this->{"_liquidRescale"}($width, $height, $deltaX, $rigidity);
        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param int|null $offsetX
     * @param int|null $offsetY
     * @throws Exception
     * @return Adapter
     */
    public function crop($width, $height, $offsetX = null, $offsetY = null)
    {
        if ((!is_int($width)) || !is_int($height) || (!is_int($offsetX) && !is_null($offsetX)) || (!is_int($offsetY) && !is_null($offsetY))) {
            throw new Exception('Invalid parameter type.');
        }
        if (is_null($offsetX)) {
            $offsetX = (($this->_width - $width) / 2);
        } else {
            if ($offsetX < 0) {
                $offsetX = $this->_width - $width + $offsetX;
            }

            if ($offsetX > $this->_width) {
                $offsetX = (int)$this->_width;
            }
        }

        if (is_null($offsetY)) {
            $offsetY = (($this->_height - $height) / 2);
        } else {
            if ($offsetY < 0) {
                $offsetY = $this->_height - $height + $offsetY;
            }

            if ($offsetY > $this->_height) {
                $offsetY = (int)$this->_height;
            }
        }

        if ($width > ($this->_width - $offsetX)) {
            $width = $this->_width - $offsetX;
        }

        if ($height > ($this->_height - $offsetY)) {
            $height = $this->_height - $offsetY;
        }

        $this->{"_crop"}($width, $height, $offsetX, $offsetY);

        return $this;
    }

    /**
     * Rotate the image by a given amount
     *
     * @param int $degrees
     * @return Adapter
     */

    public function rotate($degrees)
    {
        $degrees = (int)$degrees;
        if ($degrees > 180) {
            // FIXME: Fix Zephir Parser to allow use  let degrees %= 360
            $degrees = $degrees % 360;
            if ($degrees > 180) {
                $degrees -= 360;
            }
        } else {
            while ($degrees < -180) {
                $degrees += 360;
            }
        }

        $this->{"_rotate"}($degrees);
        return $this;
    }

    /**
     * Flip the image along the horizontal or vertical axis
     *
     * @param int $direction
     * @return Adapter
     */
    public function flip($direction)
    {
        $direction = (int)$direction;
        if ($direction != Image::HORIZONTAL && $direction != Image::VERTICAL) {
            $direction = Image::HORIZONTAL;
        }

        $this->{"_flip"}($direction);
        return $this;
    }

    /**
     * Sharpen the image by a given amount
     *
     * @param int $amount
     * @return Adapter
     */
    public function sharpen($amount)
    {
        $amount = (int)$amount;
        if ($amount > 100) {
            $amount = 100;
        } elseif ($amount < 1) {
            $amount = 1;
        }

        $this->{"_sharpen"}($amount);
        return $this;
    }

    /**
     * Add a reflection to an image
     *
     * @param int $height
     * @param int $opacity
     * @param bool $fadeIn
     * @return Adapter
     */
    public function reflection($height, $opacity = 100, $fadeIn = false)
    {
        $height = (int)$height;
        $opacity = (int)$opacity;
        $fadeIn = (boolean)$fadeIn;
        if ($height <= 0 || $height > $this->_height) {
            $height = (int)$this->_height;
        }

        if ($opacity < 0) {
            $opacity = 0;
        } elseif ($opacity > 100) {
            $opacity = 100;
        }

        $this->{"_reflection"}($height, $opacity, $fadeIn);

        return $this;
    }

    /**
     * Add a watermark to an image with the specified opacity
     *
     * @param Adapter $watermark
     * @param int $offsetX
     * @param int $offsetY
     * @param int $opacity
     * @return mixed
     */
    public function watermark(Adapter $watermark, $offsetX = 0, $offsetY = 0, $opacity = 100)
    {
        $offsetX = (int)$offsetX;
        $offsetY = (int)$offsetY;
        $opacity = (int)$opacity;
        $tmp = $this->_width - $watermark->getWidth();

        if ($offsetX < 0) {
            $offsetX = 0;
        } elseif ($offsetX > $tmp) {
            $offsetX = $tmp;
        }

        $tmp = $this->_height - $watermark->getHeight();

        if ($offsetY < 0) {
            $offsetY = 0;
        } elseif ($offsetY > $tmp) {
            $offsetY = $tmp;
        }

        if ($opacity < 0) {
            $opacity = 0;
        } elseif ($opacity > 100) {
            $opacity = 100;
        }

        $this->{
        "_watermark"}($watermark, $offsetX, $offsetY, $opacity);

        return $this;
    }

    /**
     * @param string $text
     * @param bool $offsetX
     * @param bool $offsetY
     * @param int $opacity
     * @param string $color
     * @param int $size
     * @param string|null $fontfile
     * @throws Exception
     * @return Adapter
     */
    public function text($text, $offsetX = false, $offsetY = false, $opacity = 100, $color = "000000", $size = 12, $fontfile = null)
    {
        if (is_array($text) === false || is_int($opacity) === false ||
            is_string($color) === false || is_int($size) === false || (is_string($fontfile) === false && is_null($fontfile) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        if ($opacity < 0) {
            $opacity = 0;
        } else {
            if ($opacity > 100) {
                $opacity = 100;
            }
        }

        if (strlen($color) > 1 && substr($color, 0, 1) === "#") {
            $color = substr($color, 1);
        }

        if (strlen($color) === 3) {
            $color = preg_replace("/./", "$0$0", $color);
        }

        $colors = array_map("hexdec", str_split($color, 2));

        $this->{"_text"}($text, $offsetX, $offsetY, $opacity, $colors[0], $colors[1], $colors[2], $size, $fontfile);

        return $this;
    }

    /**
     * Composite one image onto another
     *
     * @param Adapter $watermark
     * @return Adapter
     */

    public function mask(Adapter $watermark)
    {
        $this->{"_mask"}($watermark);
        return $this;
    }

    /**
     * Set the background color of an image
     *
     * @param string $color
     * @param int $opacity
     * @throws Exception
     * @return Adapter
     */
    public function background($color, $opacity = 100)
    {
        if (is_string($color) === false ||
            is_int($opacity) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (strlen($color) > 1 && substr($color, 0, 1) === "#") {
            $color = substr($color, 1);
        }

        if (strlen($color) === 3) {
            $color = preg_replace("/./", "$0$0", $color);
        }

        $colors = array_map("hexdec", str_split($color, 2));

        $this->{"_background"}($colors[0], $colors[1], $colors[2], $opacity);
        return $this;
    }

    /**
     * Blur image
     *
     * @param int $radius
     * @return Adapter
     */
    public function blur($radius)
    {
        $radius = (int)$radius;
        if ($radius < 1) {
            $radius = 1;
        } elseif ($radius > 100) {
            $radius = 100;
        }

        $this->{"_blur"}($radius);
        return $this;
    }

    /**
     * Pixelate image
     *
     * @param int $amount
     * @return Adapter
     */
    public function pixelate($amount)
    {
        $amount = (int)$amount;
        if ($amount < 2) {
            $amount = 2;
        }

        $this->{"_pixelate"}($amount);
        return $this;
    }

    /**
     * Save the image
     *
     * @param null $file
     * @param int $quality
     * @throws Exception
     * @return Adapter
     */
    public function save($file = null, $quality = -1)
    {
        if ((!is_string($file) && !is_null($file)) ||
            is_int($quality) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (!$file) {
            $file = (string)$this->_realpath;
        }

        $this->{"_save"}($file, $quality);
        return $this;
    }

    /**
     * Render the image and return the binary string
     *
     * @param string|null $ext
     * @param int $quality
     * @throws Exception
     * @return string
     */
    public function render($ext = null, $quality = 100)
    {
        if ((!is_string($ext) && !is_null($ext)) ||
            is_int($quality) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (!$ext) {
            $ext = (string)pathinfo($this->_file, PATHINFO_EXTENSION);
        }

        if (empty($ext)) {
            $ext = "png";
        }

        if ($quality < 1) {
            $quality = 1;
        } elseif ($quality > 100) {
            $quality = 100;
        }

        return $this->{"_render"}($ext, $quality);
    }
}
