<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class CheckJwtToken
{
    public function handle(Request $request, Closure $next)
    {
        try {
           
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 401);
            }
            $tokenString = $token->get();

            $user = JWTAuth::authenticate($token);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }

            if ($user->access_token !== $tokenString) {
                return response()->json([
                    'error' => 'Token invalid or replaced by a new login session.'
                ], 401);
            }

            if ($user->token_expires_at && now()->greaterThan($user->token_expires_at)) {
                return response()->json(['error' => 'Token expired'], 401);
            }

        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalid'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        return $next($request);
    }
}
