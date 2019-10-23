<?php
namespace hypergo\utils;

class Captcha {
    private $image;
    private $width;
    private $height;
    private $fontPath;
    private $fontSize;
    private $code = "";
  
    /**
     * @param string $fontPath 字体路径
     * @param string $fontSize 字体尺寸
     * @param int $width 画布宽度
     * @param int $height 画布高度
     */
    public function __construct(string $fontPath, /* int $fontSize = 25, */ int $width = 150, int $height = 35) {
        $this->setFontPath($fontPath);
        // $this->setFontSize($fontSize);
        // 算法原理：
        // 算出画布面积，
        // 将其除以4取得四分之一的面积（因为画布上有4位字符），
        // 再将其除以2取得文字可填充区域（一个区域的一半面积为字符填充区域），
        // 然后将该面积开方并向下舍入，得到的数字可作为字体尺寸
        $this->setFontSize(floor(sqrt(($width * $height / 4) / 2)));
        $this->setHeight($height);
        $this->setWidth($width);
        $this->setImage(imagecreatetruecolor($width, $height));
    }
    
    /**
     * 设置颜色并填充图像的背景
     * @param int $x 开始填充的横坐标
     * @param int $y 开始填充的纵坐标
     */
    public function setBackground(int $x = 0, int $y = 0) {
        $color = imagecolorallocate($this->getImage(), 255, 255, 255);
        imagefill($this->getImage(), $x, $y, $color);
    }
    
    /**
     * 设置验证码形式
     * @param int $type 0为纯数字，1为纯字母，2为数字字母混合
     */
    public function setCaptcha(int $type = 2) {
        $fontColor;
        $content;
        $x;
        $y;
        $angle;
        
        for ($i = 0; $i < 4; $i++) {
            $fontColor = imagecolorallocate($this->getImage(), mt_rand(0, 150), mt_rand(0, 150), mt_rand(0, 150));
            $content = Code::getRandomCode($type, 1);
            $this->setCode($content);
            $x = $i * $this->getWidth() / 4 + mt_rand(5, 8);
            $y = mt_rand(25, 30);
            $angle = mt_rand(-20, 20);
            
            imagettftext($this->getImage(), $this->getFontSize(), $angle, $x, $y, $fontColor, $this->getFontPath(), $content);
        }
    }
    
    /**
     * 获得验证码字符串
     * return string 验证码
     */
    public function getCaptcha() {
        return strtolower($this->getCode());
    }
    
    /**
     * 为验证码添加像素点干扰
     * @param int $count 像素点的数量
     */
    public function drawPixel(int $count = 100) {
        $color;
        for ($i = 0; $i < $count; $i++) {
            $color = imagecolorallocate($this->getImage(), mt_rand(50, 200), mt_rand(50, 200), mt_rand(50, 200));
            imagesetpixel($this->getImage(), mt_rand(1, $this->getWidth()), mt_rand(1, $this->getHeight()), $color);
        }
    }
    
    /**
     * 为验证码添加线段干扰
     * @param int $count 线段的数量
     */
    public function addLine(int $count = 3) {
        $x1;
        $y1;
        $x2;
        $y2;
        $color;

        for ($i = 0; $i < $count; $i++) {
            $x1 = mt_rand(0, $this->getWidth()  / 2 - 15);
            $y1 = mt_rand(0, $this->getHeight());
            $x2 = mt_rand($this->getWidth() / 2 + 15, $this->getWidth());
            $y2 = mt_rand(0, $this->getHeight());
            $color = imagecolorallocate($this->getImage(), mt_rand(80, 230), mt_rand(80, 230), mt_rand(80, 230));
            
            imageline($this->getImage(), $x1, $y1, $x2, $y2, $color);
            imageline($this->getImage(), $x1, ++$y1, $x2, ++$y2, $color);
        }
    }

    /**
     * 绘制干扰曲线
     * y=Asin(ωx+φ)+k
     * A: 振幅，峰值，值越大，波越陡
     * ω: omega 决定周期，最小正周期为ω=2π/T
     * φ: phi 决定波形在横轴是移动的距离
     * k: 决定波形在纵轴上移动的距离
     */
    public function drawCurve() {
        $a = mt_rand(5, 15);
        $t = mt_rand(80, 130); //值越大，波越平
        $omega = 2 * M_PI / $t;
        $phi = mt_rand(0, 5);
        $k = $this->getHeight() / mt_rand(3, 5);
        //$x;
        $y;
        $color = imagecolorallocate($this->getImage(), mt_rand(50, 200), mt_rand(50, 200), mt_rand(50, 200));
        
        for ($w = 2; $w <= mt_rand(14, 18); $w += 2) { //$w: 波形的宽度
            for ($x = 0; $x < $this->getWidth(); $x++) {
                $y = $a * sin($omega * $x + $phi) + $k + $w;

                imagesetpixel($this->getImage(), $x, $y, $color);
            }
        }
    }

    /**
     * 绘制干扰文字
     */
    public function drawText() {
        $color;
        for ($i = 0; $i < 10; $i++) {   
            $color = imagecolorallocate($this->getImage(), mt_rand(150,225), mt_rand(150,225), mt_rand(150,225));
            for ($j = 0; $j < 3; $j++) {
                imagestring($this->getImage(), 4, mt_rand(3, $this->getWidth() - 12), mt_rand(1, $this->getHeight() - 16), Code::getRandomCode(2, 1), $color);   
            }
        }
    }
    
    /**
     * 设置图像资源
     * @param resource $image
     */
    public function setImage($image) {
        $this->image = $image;
        imageantialias($this->getImage(), true); //开启抗锯齿功能
    }

    /**
     * 获得图像资源
     * return resource 图像资源
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * 设置图像宽度
     * @param int $width
     */
    public function setWidth($width) {
        $this->width = $width;
    }

    /**
     * 获得图像宽度
     * return int 图像宽度
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * 设置图像高度
     * @param int $height
     */
    public function setHeight($height) {
        $this->height = $height;
    }

    /**
     * 获得图像高度
     * return int 图像高度
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * 设置字体路径
     * @param string $path
     */
    public function setFontPath($path) {
        $this->fontPath = $path;
    }

    /**
     * 获得字体路径
     * return string 字体路径
     */
    public function getFontPath() {
        return $this->fontPath;
    }

    /**
     * 获得字体尺寸
     * return int 字体尺寸
     */
    public function getFontSize() {
        return $this->fontSize;
    }

    /**
     * 设置字体尺寸
     * @param int $size
     */
    public function setFontSize($size) {
        $this->fontSize = $size;
    }

    /**
     * 获得验证随机码
     * return string 验证随机码
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * 设置验证随机码
     * @param string $code
     */
    public function setCode($code) {
        $this->code .= $code;
    }
    
    /**
     * 向页面输出一张png格式的验证码图片
     */
    public function outputPng() {
        header("Content-Type: image/png");
        imagepng($this->getImage());
    }
    
    /**
     * 销毁图像资源
     */
    public function destroy() {
        imagedestroy($this->getImage());
    }
}

?>
