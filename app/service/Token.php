<?php

declare(strict_types=1);

namespace app\service;

use Firebase\JWT\JWT;
use Swoole\Coroutine\System;
use app\entity\TokenPayload;
use think\Config;

class Token
{
  /** 令牌加密算法 */
  private $alg = 'RS256';

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
    return JWT::encode($payload, $this->privateKey, $this->alg);
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
