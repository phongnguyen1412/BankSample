<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository
{
    /**
     * @var array
     */
    protected $cache = [];

    /**
     * Find Id by Email
     *
     * @param string $email
     * @return int|null
     */
    public function findIdByEmail(string $email)
    {
        $email = strtolower(trim($email));

        if (empty($email)) {
            return null;
        }

        if (array_key_exists($email, $this->cache)) {
            return $this->cache[$email];
        }

        $customerId = Customer::query()
            ->where('email', $email)
            ->value('id');

        $this->cache[$email] = empty($customerId) ? null : (int) $customerId;

        return $this->cache[$email];
    }

    /**
     * Find Customer By Email
     *
     * @param string $email
     * @return Customer|null
     */
    public function findByEmail(string $email): ?Customer
    {
        $email = strtolower(trim($email));

        if (empty($email)) {
            return null;
        }

        return Customer::query()
            ->where('email', $email)
            ->first();
    }

    /**
     * Create User
     *
     * @param array $data
     * @return Customer
     */
    public function create(array $data): Customer
    {
        return Customer::query()->create($data);
    }
}
