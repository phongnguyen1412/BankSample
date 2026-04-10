<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CurrentCustomerTransactionRequest;
use App\Models\Customer;
use App\Repositories\TransactionRecordRepository;
use Illuminate\Http\JsonResponse;
use Throwable;

class TransactionController extends Controller
{
    protected $transactionRecordRepository;

    public function __construct(TransactionRecordRepository $transactionRecordRepository)
    {
        $this->transactionRecordRepository = $transactionRecordRepository;
    }

    public function __invoke(CurrentCustomerTransactionRequest $request): JsonResponse
    {
        $customer = $request->user();
        $perPage = (int) $request->input('per_page', 10);

        if (! $customer instanceof Customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
            ], 404);
        }

        try {
            $transactions = $this->transactionRecordRepository->getTransactionByCustomerId((int) $customer->id, $perPage);

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

    protected function formatTransactions(array $transactions): array
    {
        $items = [];

        foreach ($transactions as $transaction) {

            $items[] = [
                'transaction_uid' => (string) $transaction->transaction_uid,
                'date' => (string) $transaction->date,
                'content' => (string) $transaction->content,
                'amount' => ($transaction->amount > 0 ? '+' : '') . (float) $transaction->amount,
                'type' => (int) $transaction->type === 1 ? 'Deposit' : 'Withdraw',
            ];
        }

        return $items;
    }
}
