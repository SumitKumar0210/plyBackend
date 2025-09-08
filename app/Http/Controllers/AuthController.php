<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use Mail;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register','forgotPassword','resetPassword','updatePassword']]);
    }

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'type' => 'required|string|in:admin,customer,dealer'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => $request->type,
        ]);

        // Login user immediately
        $token = auth()->login($user);

        // Set same expiry as login
        $expiresInSeconds = 60 * 60 * 24 * 365; // 1 year
        $tokenExpiresAt = now()->addSeconds($expiresInSeconds);

        $user->access_token = $token;
        $user->token_expires_at = $tokenExpiresAt;
        $user->save();

        return $this->respondWithToken($token, $expiresInSeconds);
    }


    /**
     * Login
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();

        // Example: 1 year expiry
        $expiresInSeconds = 60 * 60 * 24 * 365;
        $tokenExpiresAt = now()->addSeconds($expiresInSeconds);

        $user->access_token = $token;
        $user->token_expires_at = $tokenExpiresAt;
        $user->save();

        return $this->respondWithToken($token, $expiresInSeconds);
    }

    /**
     * Change password (user must be logged in)
     */
    public function changePassword(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'password' => 'required|string|min:6|old_password|confirmed',
            'new_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = auth()->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['error' => 'Old password is incorrect'], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'Password changed successfully']);
    }

    /**
     * Get the authenticated User
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Logout
     */
    public function logout()
    {
         try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'status'  => 'success',
                'message' => 'User successfully logged out.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Sorry, the user cannot be logged out.'
            ], 500);
        }
    }

    /**
     * Refresh token
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh(), auth()->factory()->getTTL() * (365 * 24 * 60));
    }

    /**
     * Respond with token
     */
    protected function respondWithToken($token, $expiresInSeconds)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $expiresInSeconds
        ]);
    }
    
    public function forgotPassword(Request $request)
    {
      
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Token & expiry
        $expire_at = \Carbon\Carbon::now()->addMinutes(10); // Token valid for 10 minutes
        $token = bin2hex(random_bytes(16)); // Plain token (to send in mail)

        // Save hashed token & expiry in DB
        $user->update([
            'reset_token' => Hash::make($token),   
            'reset_link_expires_at' => $expire_at
        ]);

        // Generate reset link (send plain token)
        $url = url('/api/reset-password?id='.$user->id.'&token='.$token);

        // Send mail
        Mail::raw('Click here to reset your password: '.$url, function ($message) use ($user) {
            $message->to('sumitkr56569@gmail.com')
                    ->subject('Password Reset Request');
        });

        return response()->json(['message' => 'Password reset link has been sent to your email.']);
    }



    public function resetPassword(Request $request)
    {
       
        $token = $request->query('token');
        $user_id = $request->query('id');
        if (!$token || !$user_id) {
            return response()->json(['error' => 'Token and user ID are required'], 400);
        }

        $user = User::find($user_id);
        
        if(!$user)
            {
                return response()->json(['error' => 'User not found'], 400);
            }

           
            
        // Check if token is valid and not expired
        if (!($token == $user->reset_token) || \Carbon\Carbon::now()->greaterThan($user->reset_link_expires_at)) {
            return response()->json(['error' => 'Invalid or expired token'], 400);
        }

        return response()->json(['message' => 'token validation completed.']);
    }
    public function updatePassword(Request $request, $id)
    {
       
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check if token is valid and not expired
        if (!($request->token == $user->reset_token)) {
            return response()->json(['error' => 'Invalid or expired token'], 400);
        }

        // Update the user's password
        $user->update([
            'password' => Hash::make($request->password),
            'reset_token' => null,
            'reset_link_expires_at' => null,
        ]);

        return response()->json(['message' => 'Password has been reset successfully.']);
    }
}
