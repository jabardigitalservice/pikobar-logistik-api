<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use JWTAuth;

class AuthKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $JWTtoken = JWTAuth::getToken();
        $token = $request->header('Api-Key');
        $authKey = \App\AuthKey::whereToken($token)->first();
        $response = $next($request);
        if (!isset($authKey)) {
            $response = response()->format(Response::HTTP_UNAUTHORIZED, 'Unauthenticated');
            if ($JWTtoken) {
                $response = $this->isHasJWTToken($request, $next);
            }
        }
        return $response;
    }

    public function isHasJWTToken($request, Closure $next)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                $response = response()->format(Response::HTTP_NOT_FOUND, 'user_not_found');
            }
            $request->merge(array("authenticated_user_id" => $user->id));
            $response = $next($request);
        } catch (TokenExpiredException $e) {
            $response = $this->refreshToken($request);
        } catch (JWTException $e) {
            $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $e->getMessage());
        } catch (Exception $exception) {
            $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, 'token_failure');
        }
        return $response;
    }

    public function refreshToken($request)
    {
        $token = $request->token;
        $refreshedToken = JWTAuth::refresh($token);
        return response()->format(Response::HTTP_OK, "token_expired", ["new_token" => $refreshedToken]);
    }
}
