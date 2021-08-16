<?php

declare(strict_types=1);

namespace app\service;

use app\core\Result;
use app\core\storage\Storage;
use app\facade\UserService;
use think\facade\Request;

class Auth
{
  private $tokenService;

  public function __construct(Token $tokenService)
  {
    $this->tokenService = $tokenService;
  }

  /**
   * 通过续签令牌来刷新访问令牌
   *
   * @param string $jwt 续签令牌
   * @return Result
   */
  public function refresh(string $jwt): Result
  {
    try {
      $payload = $this->tokenService->parse($jwt);

      if (!$this->tokenService->isAvailable($payload)) {
        return Result::create(Result::CODE_AUTH_EXPIRES);
      }

      $payload = $this->tokenService->refresh($payload);
      $token   = $this->tokenService->issue($payload);

      return Result::success($token);
    } catch (\Exception $e) {
      return Result::create(Result::CODE_AUTH_EXPIRES);
    }
  }

  /**
   * 获取令牌主人信息
   *
   * @return Result
   */
  public function info(): Result
  {
    $token   = Request::header('Authorization');
    $payload = $this->tokenService->parse($token);
    $fields  = User::USER_FIELDS;
    $user    = UserService::getByKey('id', $payload->sub, $fields);
    $storage = Storage::create();

    $user->avatarThumbnail = $storage->getThumbnailUrl($user->avatar);
    $user->avatar          = $storage->getUrl($user->avatar);

    return Result::success($user);
  }

  /**
   * 退出认证，废弃令牌
   *
   * @return void
   */
  public function logout(): void
  {
    $token   = Request::header('Authorization');
    $payload = $this->tokenService->parse($token);

    $this->tokenService->disuse($payload);
  }
}
