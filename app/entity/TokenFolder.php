<?php

declare(strict_types=1);

namespace app\entity;

class TokenFolder
{
  /**
   * 访问令牌
   *
   * @var string
   */
  public $access;

  /**
   * 续签令牌
   *
   * @var string
   */
  public $refresh;

  public function __construct(string $access, string $refresh)
  {
    $this->access = $access;
    $this->refresh = $refresh;
  }
}
