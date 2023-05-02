<?php

namespace App\Http\Controllers;

use App\Mail\PasswordUpdateMail;
use App\Mail\ResetPasswordMail;
use App\Models\PasswordReset;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'lastname'      => 'required|string|max:255',
            'firstname'     => 'required|string|max:255',
            'middlename'     => 'required|string|max:255',
            'email'         => 'required|email|max:255|unique:users,email',
            'password'      => 'required|string|max:255|confirmed',
            'image'         => 'nullable|image|max:3000'
        ]);


        $image_name = null;
        // Check if an image is uploaded
        if ($request->hasFile('image')){
            $image_name = $this->saveUserImage($request->image, 'images');
        }

        $user = User::create([
            'lastname'      => $request->lastname,
            'firstname'     => $request->firstname,
            'middlename'    => $request->middlename,
            'email'         => $request->email,
            'image'         => $image_name,
            'password'      => $request->password
        ]);

        $token = $user->createToken('myapptoken')->plainTextToken;

        return $this->successResponse([
            'errorCode' => 'SUCCESS',
            'token'     => $token,
            'data'      => $user
        ],201);
    }

    public function login(Request $request): JsonResponse
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        // Check email
        $user = User::where('email', $fields['email'])->first();

        // Check password
        if(!$user || !Hash::check($fields['password'], $user->password)) {
            return $this->errorResponse([
                'errorCode' => 'AUTHENTICATION_ERROR',
                'message' => 'Invalid username and password'
            ], 401);
        }

        if (!$user->active){
            return $this->errorResponse([
                'errorCode' => 'AUTHENTICATION_ERROR',
                'message' => 'Inactive account'
            ], 401);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        return $this->successResponse([
            'errorCode' => 'SUCCESS',
            'token'     => $token,
            'data'      => $user
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        auth()->user()->tokens()->delete();

        return $this->successResponse([
            'errorCode' => 'SUCCESS',
            'message'   => 'Logged out'
        ], 200);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user){
            return $this->errorResponse([
                'errorCode' => "AUTHENTICATION_ERROR",
                'message' => 'Email not found'
            ], 404);
        }

        // Check if exist then update else create
        $reset = PasswordReset::where('email', $request->email)->first();
        $token = Str::random(50);
        if ($reset){
            $reset->token = $token;
            $reset->expires_at = Carbon::now()->addMinutes(10);
            $reset->save();
        }else{
            PasswordReset::create([
                'email'        => $request->email,
                'token'        => $token,
                'expires_at'   => Carbon::now()->addMinutes(10)
            ]);
        }

        // Mail the contact concerning the update
        Mail::to($user->email)->send(new ResetPasswordMail($user, $token));

        // Send r
        return $this->errorResponse([
            'errorCode' => "SUCCESS",
            'message' => 'Reset link has been sent to you email address'
        ], 202);

    }

    public function chooseNewPassword(Request $request, $token): JsonResponse
    {
        $request->validate([
            'password'  => 'required|string|confirmed'
        ]);

        $token = PasswordReset::where('token', $token)->first();
        if (!$token){
            return $this->errorResponse([
                'errorCode' => "VALIDATION_ERROR",
                'message' => 'Invalid reset token'
            ], 422);
        }

        $tokenDate = Carbon::parse($token->expires_at);
        // Check if it has not expired
        if ($tokenDate <= Carbon::now()){
            // Delete the token and send response to contact
            $token->delete();
            return $this->errorResponse([
                'errorCode' => "VALIDATION_ERROR",
                'message' => 'Invalid reset token'
            ], 422);
        }

        // Update contact password
        $user = User::where('email', $token->email)->first();
        $user->password = $request->password;
        $user->save();

        // Delete token
        $token->delete();

        // Mail the contact concerning the update
        Mail::to($user->email)->send(new PasswordUpdateMail($user));

        // Send r
        return $this->errorResponse([
            'errorCode' => "SUCCESS",
            'message' => 'Your password has been updated successfully'
        ], 202);


    }
}
