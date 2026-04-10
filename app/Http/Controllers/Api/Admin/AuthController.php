<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }
        
        $user = $this->findUserByEmail($request->input('email'));
        $password = (string)$request->input('password');
        
        if (!$this->isValidPassword($user, $password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }
        
        $token = $user->createToken('import-api')->plainTextToken;
        
        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
    
    /**
     * @param $email
     * @return User|null
     */
    protected function findUserByEmail($email): ?User {
        return User::query()
            ->where('email', (string)$email)
            ->first();
    }
    
    /**
     * @param User|null $user
     * @param string $password
     * @return bool
     */
    protected function isValidPassword(?User $user, string $password): bool {
        if (empty($user)) {
            return false;
        }
        
        return Hash::check($password, $user->password);
    }
}
