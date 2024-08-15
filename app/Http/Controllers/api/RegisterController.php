<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Http\JsonResponse;

class RegisterController extends BaseController
{
    /**
     * Register API
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);

        if($validator->fails()){
            // Returning a JsonResponse directly
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors()
            ], 400); // 400 Bad Request
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        $success['token'] =  $user->createToken('MyApp')->plainTextToken;
        $success['name'] =  $user->name;

        // Returning a JsonResponse directly
        return response()->json([
            'success' => true,
            'data' => $success,
            'message' => 'User registered successfully.'
        ], 201); // 201 Created
    }

    /**
     * Login API
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
            $user = Auth::user(); 
            $success['token'] =  $user->createToken('MyApp')->plainTextToken; 
            $success['name'] =  $user->name;

            // Returning a JsonResponse directly
            return response()->json([
                'success' => true,
                'data' => $success,
                'message' => 'User logged in successfully.'
            ], 200); // 200 OK
        } else { 
            // Returning a JsonResponse directly
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
                'error' => 'Unauthorized'
            ], 401); // 401 Unauthorized
        } 
    }
}
