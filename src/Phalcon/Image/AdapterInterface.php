<?php
namespace Phalcon\Image;

use Phalcon\Image;
interface AdapterInterface
{
    /**
     * @param int|null $width
     * @param int|null $height
     * @param int $master
     * @return mixed
     */
    public function resize($width = null,$height = null,$master = Image::AUTO);

    /**
     * @param int $width
     * @param int $height
     * @param int|null $offsetX
     * @param int|null $offsetY
     * @return mixed
     */
    public function crop($width,$height,$offsetX = null,$offsetY = null);

    /**
     * @param int $degrees
     * @return mixed
     */
    public function rotate($degrees);

    /**
     * @param int $direction
     * @return mixed
     */
    public function flip($direction);

    /**
     * @param int $amount
     * @return mixed
     */
    public function sharpen($amount);

    /**
     * @param int $height
     * @param int $opacity
     * @param bool $fadeIn
     * @return mixed
     */
    public function reflection($height, $opacity = 100, $fadeIn = false);

    /**
     * @param Adapter $watermark
     * @param int $offsetX
     * @param int $offsetY
     * @param int $opacity
     * @return mixed
     */
    public function watermark(Adapter $watermark,$offsetX = 0,$offsetY = 0,$opacity = 100);

    /**
     * @param string $text
     * @param int $offsetX
     * @param int $offsetY
     * @param int $opacity
     * @param string $color
     * @param int $size
     * @param string|null $fontfile
     * @return mixed
     */
	public function text($text, $offsetX = 0,$offsetY = 0,$opacity = 100,$color = "000000",$size = 12,$fontfile = null);

    /**
     * @param Adapter $watermark
     * @return mixed
     */
	public function mask(Adapter $watermark);

    /**
     * @param string $color
     * @param int $opacity
     * @return mixed
     */
	public function background($color,$opacity = 100);

    /**
     * @param int $radius
     * @return mixed
     */
	public function blur($radius);

    /**
     * @param int $amount
     * @return mixed
     */
	public function pixelate($amount);

    /**
     * @param string|null $file
     * @param int $quality
     * @return mixed
     */
	public function save($file = null, $quality = 100);

    /**
     * @param string|null $ext
     * @param int $quality
     * @return mixed
     */
	public function render($ext = null,$quality = 100);
}
