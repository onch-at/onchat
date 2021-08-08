<?php

declare(strict_types=1);

namespace app\service;

use Firebase\JWT\JWT;
use Swoole\Coroutine\System;
use app\constant\RedisPrefix;
use app\core\Redis;
use app\entity\TokenPayload;
use think\Config;

class Token
{
  /** 令牌加密算法 */
  private const ALG = 'RS256';

  private $privateKey;
  private $publicKey;

  private $config;

  public function __construct(Config $config)
  {
    $this->privateKey = System::readFile(root_path() . 'private-key.pem');
    $this->publicKey  = System::readFile(root_path() . 'public-key.pem');

    $this->config = $config;
  }

  /**
   * 生成 TokenPayload
   *
   * @param string $name
   * @param integer $subject
   * @param integer $ttl 存活时间
   * @return TokenPayload
   */
  public function generate(int $subject, int $ttl): TokenPayload
  {
    ['iss' => $issuer, 'aud' => $audience] = $this->config->get('jwt');

    $payload = TokenPayload::create($subject, $ttl);
    $payload->iss = $issuer;
    $payload->aud = $audience;

    return $payload;
  }

  /**
   * 颁发令牌
   *
   * @param TokenPayload $payload
   * @return string
   */
  public function issue(TokenPayload $payload): string
  {
    return JWT::encode($payload, $this->privateKey, self::ALG);
  }

  /**
   * 解析 JWT 返回 payload
   *
   * @param string $jwt
   * @return TokenPayload
   */
  public function parse(string $jwt): TokenPayload
  {
    $payload = JWT::decode($jwt, $this->publicKey, [self::ALG]);
    return TokenPayload::from($payload);
  }

  /**
   * 是否被废弃了
   * 如果 JTI 变了，说明被其他客户端更新了令牌
   *
   * @return boolean
   */
  public function isAvailable(TokenPayload $payload)
  {
    return $payload->jti === Redis::create()->get(RedisPrefix::JWT_ID . $payload->sub);
  }

  /**
   * 废弃令牌
   *
   * @param TokenPayload $payload
   * @return void
   */
  public function disuse(TokenPayload $payload)
  {
    Redis::create()->del(RedisPrefix::JWT_ID . $payload->sub);
  }
}
