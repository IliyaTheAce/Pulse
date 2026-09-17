<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\AuthExceptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticationController extends Controller
{

    public function Register(RegisterRequest $request): JsonResponse
    {
        $request->validated();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
        ]);

        event(new Registered($user));

        $token = $user->createToken('api_token')->plainTextToken;
        $user = new UserResource($user);
        return $this->successResponse(__("user_created"), compact('user', 'token'));
    }

    public function Login(LoginRequest $request): JsonResponse
    {
        $request->validated();

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw AuthExceptions::invalidCredentials();
        }

        $token = $user->createToken('api_token')->plainTextToken;
        $user = new UserResource($user);
        return $this->successResponse(__("user_logged_in"), compact('token', 'user'));
    }

    public function GetUser(Request $request)
    {
        $user = $request->user();
        $user->load('teams');
        return $this->successResponse(null, new UserResource($user));
    }


}
