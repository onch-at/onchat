<?php

namespace app\core\identicon\generator;

use ImagickDraw;
use ImagickPixel;
use Identicon\Generator\ImageMagickGenerator as OriginImageMagickGenerator;

/**
 * 重写OriginImageMagickGenerator的部分方法
 */
class ImageMagickGenerator extends OriginImageMagickGenerator
{
    /**
     * @return $this
     */
    private function generateImage()
    {
        $this->generatedImage = new \Imagick();
        $rgbBackgroundColor = $this->getBackgroundColor();

        if (null === $rgbBackgroundColor) {
            $background = 'none';
        } else {
            $background = new ImagickPixel("rgb($rgbBackgroundColor[0],$rgbBackgroundColor[1],$rgbBackgroundColor[2])");
        }

        $size = $this->pixelRatio * 6;
        $this->generatedImage->newImage($size, $size, $background, 'png');

        // prepare color
        $rgbColor = $this->getColor();
        $color = new ImagickPixel("rgb($rgbColor[0],$rgbColor[1],$rgbColor[2])");

        $draw = new ImagickDraw();
        $draw->setFillColor($color);

        $offset = $this->pixelRatio / 2;

        // draw the content
        foreach ($this->getArrayOfSquare() as $lineKey => $lineValue) {
            foreach ($lineValue as $colKey => $colValue) {
                if (true === $colValue && $lineKey < 5) { // 只要 0~4 行
                    $draw->rectangle(
                        $colKey * $this->pixelRatio + $offset,
                        $lineKey * $this->pixelRatio + $offset,
                        ($colKey + 1) * $this->pixelRatio + $offset,
                        ($lineKey + 1) * $this->pixelRatio + $offset
                    );
                }
            }
        }

        $this->generatedImage->drawImage($draw);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getImageBinaryData($string, $size = null, $color = null, $backgroundColor = null)
    {
        ob_start();
        echo $this->getImageResource($string, $size, $color, $backgroundColor);
        $imageData = ob_get_contents();
        ob_end_clean();

        return $imageData;
    }

    /**
     * {@inheritdoc}
     */
    public function getImageResource($string, $size = null, $color = null, $backgroundColor = null)
    {
        $this
            ->setString($string)
            ->setSize($size)
            ->setColor($color)
            ->setBackgroundColor($backgroundColor)
            ->generateImage();

        return $this->generatedImage;
    }

    /**
     * Set the image size.
     *
     * @param int $size
     *
     * @return $this
     */
    public function setSize($size)
    {
        if (null === $size) {
            return $this;
        }

        $this->size = $size;
        $this->pixelRatio = (int) round($size / 6);

        return $this;
    }
}
