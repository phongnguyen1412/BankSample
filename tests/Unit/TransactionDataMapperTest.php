<?php

namespace Tests\Unit;

use App\Repositories\CustomerRepository;
use App\Services\Csv\TransactionRecordCsvDataMapper;
use Nette\Schema\ValidationException;
use Tests\TestCase;

class TransactionDataMapperTest extends TestCase
{
    public function test_valid_dump_data(): void
    {
        $customerRepository = $this->createMock(CustomerRepository::class);
        $customerRepository
            ->method('findIdByEmail')
            ->willReturn(1);

        $mapper = new TransactionRecordCsvDataMapper($customerRepository);

        $result = $mapper->map(
            ['date', 'content', 'amount', 'type', 'customer_email'],
            ['2026-04-10 10:00:00', 'Salary payment', '+100.50', 'deposit', 'customer@example.com']
        );

        $this->assertSame([
            'customer_id' => 1,
            'date' => '2026-04-10 10:00:00',
            'content' => 'Salary payment',
            'amount' => '+100.50',
            'type' => 1,
        ], $result);
    }

    public function test_invalid_type(): void
    {
        $customerRepository = $this->createMock(CustomerRepository::class);
        $customerRepository
            ->method('findIdByEmail')
            ->willReturn(1);

        $mapper = new TransactionRecordCsvDataMapper($customerRepository);

        $this->expectException(ValidationException::class);

        $mapper->map(
            ['date', 'content', 'amount', 'type', 'customer_email'],
            ['2026-04-10 10:00:00', 'Salary payment', '+100.50', 'transfer', 'customer@example.com']
        );
    }

    public function test_invalid_customer_email(): void
    {
        $customerRepository = $this->createMock(CustomerRepository::class);
        $mapper = new TransactionRecordCsvDataMapper($customerRepository);

        $this->expectException(ValidationException::class);

        $mapper->map(
            ['date', 'content', 'amount', 'type', 'customer_email'],
            ['2026-04-10 10:00:00', 'Salary payment', '+100.50', 'deposit', 'dumpData']
        );
    }
}
