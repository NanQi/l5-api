<?php

namespace Specialtactics\L5Api\Http\Controllers\Features;

use Illuminate\Http\Request;
use Dingo\Api\Http\Response;
use Specialtactics\L5Api\Exceptions\UnauthorizedHttpException;

trait JWTAuthenticationTrait
{
    /**
     * Get a JWT via given credentials.
     *
     * @param Request $request
     * @return Response
     */
    public function token(Request $request)
    {
        $authHeader = $request->header('Authorization');

        // Get for Auth Basic
        if (strtolower(substr($authHeader, 0, 5)) !== 'basic') {
            throw new UnauthorizedHttpException('无效的授权头部，请使用basic');
        }

        // Get credentials
        $credentials = base64_decode(trim(substr($authHeader, 5)));

        [$login, $password] = explode(':', $credentials, 2);

        // Do auth
        if (! $token = auth()->attempt(['login' => $login, 'password' => $password])) {
            throw new UnauthorizedHttpException('登录未授权');
        }

        return $this->respondWithToken($token);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return Response
     */
    public function logout()
    {
        auth()->logout();

        return $this->response->noContent();
    }

    /**
     * Refreshes a jwt (ie. extends it's TTL)
     *
     * @return Response
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return Response
     */
    public function getUser()
    {
        return $this->api->raw()->get('users/' . $this->auth->user()->getKey());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     * @return Response
     */
    protected function respondWithToken($token)
    {
        $tokenReponse = new \Stdclass;

        $tokenReponse->jwt = $token;
        $tokenReponse->token_type = 'bearer';
        $tokenReponse->expires_in = auth()->factory()->getTTL();

        return $this->response->item($tokenReponse, $this->getTransformer())->setStatusCode(200);
    }
}
