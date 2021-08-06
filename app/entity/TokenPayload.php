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
   * @var integer
   */
  public $sub;

  /**
   * Issued At 签发时间
   *
   * @var integer
   */
  public $iat;

  /**
   * Not Before 开始时间
   *
   * @var integer
   */
  public $nbf;

  /**
   * Expire 过期时间
   *
   * @var integer
   */
  public $exp;

  /**
   * JWT ID
   *
   * @var string
   */
  public $jti;

  /**
   * 存活时间
   *
   * @var integer
   */
  public $ttl;

  /**
   * 用户数据
   *
   * @var object|null
   */
  public $usr;

  public static function create(int $subject, int $ttl): self
  {
    $redis = Redis::create();
    $key   = RedisPrefix::JWT_ID . $subject;
    $jti   = $redis->get($key);
    $time  = time();

    if (!$jti) {
      $jti = md5(uniqid((string) $subject, true));
      $redis->set($key, $jti, $ttl);
    }

    $payload = new self();
    $payload->sub = $subject;
    $payload->iat = $time;
    $payload->nbf = $time;
    $payload->exp = $time + $ttl;
    $payload->ttl = $ttl;
    $payload->jti = $jti;

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
