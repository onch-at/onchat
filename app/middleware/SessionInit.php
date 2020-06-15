<?php

declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\App;
use think\Request;
use think\Response;
use think\Session;
use think\middleware\SessionInit as BaseSessionInit;

/**
 * 自定义的SessionInit初始化
 */
class SessionInit extends BaseSessionInit
{

    public function __construct(App $app, Session $session)
    {
        parent::__construct($app, $session);
    }

    /**
     * Session初始化
     * @access public
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        // Session初始化
        $varSessionId = $this->app->config->get('session.var_session_id');
        $cookieName   = $this->session->getName();

        if ($varSessionId && $request->request($varSessionId)) {
            $sessionId = $request->request($varSessionId);
        } else {
            $sessionId = $request->cookie($cookieName);
        }

        if ($sessionId) {
            $this->session->setId($sessionId);
        }

        $this->session->init();

        $request->withSession($this->session);

        /** @var Response $response */
        $response = $next($request);

        $response->setSession($this->session);

        // $this->app->cookie->set($cookieName, $this->session->getId());
        // COOKIE的有效期 取 SESSION的有效期
        $this->app->cookie->set($cookieName, $this->session->getId(), $this->session->getConfig('expire'));

        return $response;
    }
}
