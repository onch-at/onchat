<?php

namespace app\middleware;

use app\core\Result;
use think\Response;
use think\response\Json;

class Jsonify
{
  public function handle($request, \Closure $next)
  {
    /** @var Response */
    $response = $next($request);

    if ($response->getData() instanceof Result) {
      return $response->getData()->toJson();
    }

    if (!$response instanceof Json) {
      return Json::create($response->getData(), 'json', $response->getCode());
    }

    return $response;
  }
}
