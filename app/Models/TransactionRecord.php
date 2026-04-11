<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class TransactionRecord extends Model
{
    /**
     * @var string
     */
    protected $table = 'transaction_record';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array[]
     */
    protected $fillable = [
        'transaction_uid',
        'customer_id',
        'date',
        'content',
        'amount',
        'type',
        'created_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:3',
        'customer_id' => 'integer',
        'type' => 'integer',
    ];

    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'transaction_uid' => ['required', 'string', 'size:64'],
            'customer_id' => ['required', 'integer', 'exists:customer,id'],
            'date' => ['required', 'date_format:Y-m-d H:i:s'],
            'content' => [
                'required',
                'string',
                'regex:/\S/',
                'not_regex:/<[^>]*>/',
            ],
            'amount' => [
                'required',
                'regex:/^[-+]?\d+(\.\d+)?$/',
            ],
            'type' => [
                'required',
                'integer',
                'in:1,2',
            ],
            'created_at' => ['required', 'date'],
        ];
    }

    /**
     * Validate
     *
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateBeforeSave(): void
    {
        Validator::make($this->attributesToArray(), $this->rules())->validate();
    }

    /**
     * @inheritDoc
     */
    public function save(array $options = []): bool
    {
        $this->validateBeforeSave();

        return parent::save($options);
    }
}
