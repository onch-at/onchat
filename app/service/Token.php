<?php

declare(strict_types=1);

namespace app\service;

use Firebase\JWT\JWT;
use Swoole\Coroutine\System;
use app\entity\TokenPayload;
use think\Config;
use think\Cookie;

class Token
{
  /** 令牌加密算法 */
  private $alg = 'RS256';
  /** 令牌有效时间 */
  private $ttl = 86400 * 3;

  private $privateKey;
  private $publicKey;

  private $config;
  private $cookie;

  public function __construct(Config $config, Cookie $cookie)
  {
    $this->privateKey = System::readFile(root_path() . 'private-key.pem');
    $this->publicKey  = System::readFile(root_path() . 'public-key.pem');

    $this->config = $config;
    $this->cookie = $cookie;
  }

  /**
   * 颁发令牌
   *
   * @param integer $userId
   * @return string
   */
  public function issue(int $userId): string
  {
    ['name' => $name, 'iss' => $issuer, 'aud' => $audience] = $this->config->get('jwt');

    $payload = TokenPayload::create($userId, $this->ttl);
    $payload->iss = $issuer;
    $payload->aud = $audience;

    $jwt = JWT::encode($payload, $this->privateKey, $this->alg);
    $this->cookie->set($name, $jwt, $this->ttl);

    return $jwt;
  }

  /**
   * 解析 JWT 返回 payload
   *
   * @param string $jwt
   * @return TokenPayload
   */
  public function parse(string $jwt): TokenPayload
  {
    $payload = JWT::decode($jwt, $this->publicKey, [$this->alg]);
    return TokenPayload::from($payload);
  }
}
