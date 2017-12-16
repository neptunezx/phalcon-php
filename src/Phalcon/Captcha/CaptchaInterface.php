<?php

namespace Phalcon\Captcha;

interface CaptchaInterface
{

    public function createImage();

    public function getCaptchaText();
}
