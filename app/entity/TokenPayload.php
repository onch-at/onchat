<?php

declare(strict_types=1);

namespace app\entity;

use app\constant\RedisPrefix;
use app\core\Redis;

class TokenPayload
{
  /**
   * Issuer 签发者
   *
   * @var string
   */
  public $iss;

  /**
   * Audience 接收者
   *
   * @var string
   */
  public $aud;

  /**
   * Subject 拥有者
   *
   * @var int
   */
  public $sub;

  /**
   * Issued At 签发时间
   *
   * @var int
   */
  public $iat;

  /**
   * Not Before 开始时间
   *
   * @var int
   */
  public $nbf;

  /**
   * Expire 过期时间
   *
   * @var int
   */
  public $exp;

  /**
   * JWT ID
   *
   * @var string
   */
  public $jti;

  public static function create(int $subject, int $ttl)
  {
    $time = time();

    $payload = new self();
    $payload->sub = $subject;
    $payload->iat = $time;
    $payload->nbf = $time;
    $payload->exp = $time + $ttl;
    $payload->jti = md5(uniqid((string) $subject, true));

    Redis::create()->set(RedisPrefix::ACCESS_TOKEN . $subject, $payload->jti, $ttl);

    return $payload;
  }

  /**
   * 从数组或对象创建
   *
   * @param array|object $data
   * @return self
   */
  public static function from($data): self
  {
    $data    = (array) $data;
    $payload = new self();

    foreach ($data as $key => $value) {
      $payload->$key = $value;
    }

    return $payload;
  }
}
