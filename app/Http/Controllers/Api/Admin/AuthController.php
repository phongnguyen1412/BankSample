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
     * Login
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                (string) $validator->errors()->first(),
                422,
                $validator->errors()->toArray()
            );
        }

        $user = $this->findUserByEmail($request->input('email'));
        $password = (string)$request->input('password');

        if (!$this->isValidPassword($user, $password)) {
            return $this->errorResponse('Invalid email or password.', 401);
        }

        $token = $user->createToken('import-api')->plainTextToken;

        return $this->successResponse('Login successful.', [
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Find User By Email
     *
     * @param $email
     * @return User|null
     */
    protected function findUserByEmail($email): ?User
    {
        return User::query()
            ->where('email', (string)$email)
            ->first();
    }

    /**
     * @param User|null $user
     * @param string $password
     * @return bool
     */
    protected function isValidPassword(?User $user, string $password): bool
    {
        if (empty($user)) {
            return false;
        }

        return Hash::check($password, $user->password);
    }
}
