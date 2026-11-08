<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => 'nullable',
            'new_category_name' => 'nullable|string|max:100',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|gt:0',
            'description' => 'required|string|max:500',
            'recipient' => 'nullable|string|max:150',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $catId = $this->input('expense_category_id');
            $newCat = trim((string) $this->input('new_category_name'));

            if ((empty($catId) || $catId === '__new__') && empty($newCat)) {
                $validator->errors()->add('expense_category_id', 'Pilih salah satu kategori pengeluaran atau ketik kategori baru.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $val = $this->amount;
            $clean = ($val === '-' || $val === '' || is_null($val)) ? 0 : (float) preg_replace('/[^\d]/', '', (string) $val);
            $this->merge(['amount' => $clean]);
        }
    }
}

