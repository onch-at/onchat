<?php

declare(strict_types=1);

namespace app\entity;

class ImageMessage
{
  /** 文件名 */
  public $filename;
  /** 宽度 */
  public $width;
  /** 高度 */
  public $height;
  /** 原图URL */
  public $url;
  /** 缩略图URL */
  public $thumbnailUrl;

  public function __construct(string $filename, int $width, int $height)
  {
    $this->filename = $filename;
    $this->width    = $width;
    $this->height   = $height;
  }
}
