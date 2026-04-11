<?php

namespace App\Http\Controllers\Api\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerTransactionRequest;
use App\Repositories\CustomerRepository;
use App\Repositories\TransactionRecordRepository;
use Illuminate\Http\JsonResponse;
use Throwable;

class TransactionController extends Controller
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;
    
    /**
     * @var TransactionRecordRepository
     */
    protected $transactionRecordRepository;
    
    /**
     * @param CustomerRepository $customerRepository
     * @param TransactionRecordRepository $transactionRecordRepository
     */
    public function __construct(
        CustomerRepository $customerRepository,
        TransactionRecordRepository $transactionRecordRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->transactionRecordRepository = $transactionRecordRepository;
    }

    /**
     * Get Transaction
     *
     * @param CustomerTransactionRequest $request
     * @return JsonResponse
     */
    public function __invoke(CustomerTransactionRequest $request): JsonResponse
    {
        $email = strtolower(trim((string)$request->input('email')));
        $perPage = (int)$request->input('per_page', 10);
        $customer = $this->customerRepository->findByEmail($email);
        if ($customer === null) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
            ], 404);
        }
        try {
            $transactions = $this->transactionRecordRepository
                ->getTransactionByCustomerId((int) $customer->id, $perPage);
            return response()->json([
                'success' => true,
                'message' => 'Success',
                'customer' => [
                    'email' => (string) $customer->email,
                    'name' => (string) $customer->name,
                ],
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
                'items' => $this->formatTransactions($transactions->items()),
            ]);
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json([
                'success' => false,
                'message' => 'Server error. Please try again later.',
            ], 500);
        }
    }

    /**
     * Format Transaction
     *
     * @param array $transactions
     * @return array
     */
    protected function formatTransactions(array $transactions): array
    {
        $items = [];
        foreach ($transactions as $transaction) {
            $items[] = [
                'transaction_uid' => (string)$transaction->transaction_uid,
                'date' => (string)$transaction->date,
                'content' => (string)$transaction->content,
                'amount' => ($transaction->amount > 0 ? '+' : '') . (float)$transaction->amount,
                'type' => (int)$transaction->type === 1 ? 'Deposit' : 'Withdraw',
            ];
        }

        return $items;
    }
}
