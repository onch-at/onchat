<?php

declare(strict_types=1);

namespace app\entity;

class VoiceMessage
{
  /** 音频URL */
  public $url;
  /** 文件名 */
  public $filename;
  /** 音频时长 */
  public $duration;
  /** 已读列表 */
  public $readedList;

  public function __construct(string $filename, $duration)
  {
    $this->filename   = $filename;
    $this->duration   = $duration >= 1 ? (int) $duration : +number_format($duration, 1);
    $this->readedList = [];
  }
}
