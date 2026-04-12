<?php

namespace App\Modules\Admin\Http\Controllers;



use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

          $user = User::where('email', $request->email)->first();

        // Verify password WITHOUT starting a session
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

       return response()->json([
           'message' => 'Login successful',
           'user' => $user,
            'token' => $user->createToken('API')->plainTextToken // Create a token for the user
        ]);
        // For production
        // Token abilities
        // $token = $user->createToken('mobile', ['posts:create']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}



