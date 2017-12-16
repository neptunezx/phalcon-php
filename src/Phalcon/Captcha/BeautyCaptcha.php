<?php

namespace Phalcon\Captcha;

/**
 * Phlacon\Captcha\BeautyCaptcha
 * 
 * Generate beauty captcha image
 * 
 * <code>
 * use Phlacon\Captcha\BeautyCaptcha;
 * 
 * $captcha = new BeautyCaptcha();
 * $captcha->CreateImage();
 * </code>
 */
class BeautyCaptcha implements CaptchaInterface
{

    private $charset                = 'abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789'; //随机因子
    private $code; //验证码
    private $codeLength             = 4; //验证码长度
    private $width                  = 200; //宽度
    private $height                 = 70; //高度
    private $img; //图形资源句柄
    private $font; //指定的字体
    private $fontSize               = 20; //指定字体大小
    private $fontColor;
    private $backgroundColor;
    private $minWordLength          = 4;
    private $maxWordLength          = 4;
    public static $fontResourcePath = __DIR__ . "/resources/fonts/";
    public static $fonts            = [
        'AntykwaBold.ttf', 'Candice.ttf',
        'Duality.ttf', 'Jura.ttf', 'StayPuft.ttf',
        'TimesNewRomanBold.ttf', 'VeraSansBold.ttf'
    ];

    //构造方法初始化
    public function __construct(array $options = null)
    {
        $optionKeys = [
            'width', 'height', 'codeLength', 'fontSize', 'font',
            'fontColor', 'backgroundColor', 'minWordLength', 'maxWordLength',
        ];
        if ($options) {
            foreach ($options as $k => $v) {
                if (in_array($k, $optionKeys) && $v != null) {
                    $this->$k = $v;
                }
            }
        }
    }

    //生成随机码
    private function createCode()
    {
        $_len             = strlen($this->charset) - 1;
        $this->codeLength = $this->minWordLength == $this->maxWordLength ? $this->minWordLength : mt_rand($this->minWordLength, $this->maxWordLength);
        for ($i = 0; $i < $this->codeLength; $i++) {
            $this->code .= $this->charset[mt_rand(0, $_len)];
        }
    }

    //生成背景
    private function createBackground()
    {
        $this->img = imagecreatetruecolor($this->width, $this->height);
        if (!$this->backgroundColor) {
            $this->backgroundColor = [mt_rand(157, 255), mt_rand(157, 255), mt_rand(157, 255)];
        }
        $color = imagecolorallocate($this->img, $this->backgroundColor[0], $this->backgroundColor[1], $this->backgroundColor[2]);
        imagefilledrectangle($this->img, 0, $this->height, $this->width, 0, $color);
    }

    //生成文字
    private function createFont()
    {
        $_x = $this->width / $this->codeLength;
        for ($i = 0; $i < $this->codeLength; $i++) {
            $this->fontColor = imagecolorallocate($this->img, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            imagettftext($this->img, $this->fontSize, mt_rand(-30, 30), $_x * $i + mt_rand(1, 5), $this->height / 1.4, $this->fontColor, $this->getFont(), $this->code[$i]);
        }
    }

    //生成线条、雪花
    private function createLine()
    {
        //线条
        for ($i = 0; $i < 6; $i++) {
            $color = imagecolorallocate($this->img, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            imageline($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }
        //雪花
        for ($i = 0; $i < 100; $i++) {
            $color = imagecolorallocate($this->img, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
            imagestring($this->img, mt_rand(1, 5), mt_rand(0, $this->width), mt_rand(0, $this->height), '*', $color);
        }
    }

    //输出
    private function outputImage()
    {
        header('Content-type:image/png');
        imagepng($this->img);
        imagedestroy($this->img);
    }

    //对外生成
    public function createImage()
    {
        $this->createBackground();
        $this->createCode();
        $this->createLine();
        $this->createFont();
        $this->outputImage();
    }

    //获取验证码
    public function getCaptchaText()
    {
        if (!$this->img) {
            throw new Exception('Captcha image should be created first');
        }
        return strtolower($this->code);
    }

    public function getFont()
    {
        if ($this->font) {
            return $this->font;
        }
        return self::$fontResourcePath . self::$fonts[array_rand(self::$fonts)];
    }

}
