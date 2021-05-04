<?php

declare(strict_types=1);

namespace app\entity;

class TextMessage
{
  /** 内容 */
  public $content;

  public function __construct(string $content)
  {
    $this->content = $content;
  }
}
