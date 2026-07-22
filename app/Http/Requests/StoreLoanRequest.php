<?php

namespace App\Http\Requests;

use App\Domain\Loan\Exceptions\InvalidRequestException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use JsonException;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'customerId' => ['present', 'string', 'max:255'],
            'amount' => ['present', 'integer'],
            'phone' => ['present', 'string', 'max:255'],
            'loanType' => ['present', 'string', 'max:255'],
            'monthlyIncome' => ['present', 'integer'],
            'creditScore' => ['present', 'integer'],
            'hasGuarantor' => ['present', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        if (! $this->isJson()) {
            throw new InvalidRequestException('The request body must use JSON.');
        }

        try {
            $data = json_decode($this->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidRequestException('The request body is not valid JSON.');
        }

        if (! is_array($data)) {
            throw new InvalidRequestException('The request body must be a JSON object.');
        }

        return $data;
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new InvalidRequestException('The request structure is invalid.');
    }
}
