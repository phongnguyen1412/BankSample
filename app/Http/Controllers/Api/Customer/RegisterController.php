<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRegisterRequest;
use App\Repositories\CustomerRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
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
     * Resgiter
     *
     * @param CustomerRegisterRequest $request
     * @return JsonResponse
     */
    public function __invoke(CustomerRegisterRequest $request): JsonResponse
    {
        $email = strtolower(trim((string) $request->input('email')));

        if (!is_null($this->customerRepository->findByEmail($email))) {
            return response()->json([
                'success' => false,
                'message' => 'Customer email already exists.',
            ], 422);
        }

        $customer = $this->customerRepository->create([
            'name' => (string) $request->input('name'),
            'email' => $email,
            'password' => Hash::make((string) $request->input('password')),
        ]);

        $token = $customer->createToken('customer-api')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Customer account created successfully.',
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }
}
