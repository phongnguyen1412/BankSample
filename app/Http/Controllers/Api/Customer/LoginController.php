<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerLoginRequest;
use App\Repositories\CustomerRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;
    
    /**
     * @param CustomerRepository $customerRepository
     */
    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param CustomerLoginRequest $request
     * @return JsonResponse
     */
    public function __invoke(CustomerLoginRequest $request): JsonResponse
    {
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');
        $customer = $this->customerRepository->findByEmail($email);

        if ($customer === null || !Hash::check($password, (string) $customer->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $token = $customer->createToken('customer-api')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Customer login successful.',
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
