<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\User;

class AuthController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    } // end construct

    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $token_validity = (24 * 60);

        $this->gaurd()->factory()->setTTL($token_validity);

        if(!$token = $this->gaurd()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    } //end login

    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'phone' => 'required',
            'email' => 'required|email|unique:users,email,',
            'password' => 'required|confirmed|min:6'
        ]);

        if($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        return response()->json([
            'message' => 'User created Successfuly',
            'user' => $user
        ]);
    } // end register

    public function logout() {
        $this->gaurd()->logout();

        return response()->json(['message' => 'User Logged out Succesfuly']);
    } //end logout

    public function profile() {
        return response()->json($this->gaurd()->user());
    } //end profile

    public function refesh() {
        return $this->respondWithToken($this->gaurd()->refresh());
    } // end refresh

    protected function respondWithToken($token) {
        return response()->json(
            [
                'token' => $token,
                'token_type' => 'bearer',
                'token_validity' => ($this->gaurd()->factory()->getTTL() * 60),
            ]
        );
    }

    protected function gaurd() {
        return Auth::guard();
    }
}
