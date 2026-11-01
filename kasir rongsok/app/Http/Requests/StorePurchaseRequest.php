<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'nullable|string|max:150',
            'supplier_phone' => 'nullable|string|max:30',
            'supplier_address' => 'nullable|string|max:255',
            'save_as_supplier' => 'nullable|boolean',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'transaction_date' => 'nullable|date',
            'discount_amount' => 'nullable|numeric|min:0',
            'rounding_amount' => 'nullable|numeric',
            'debt_deduction_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.scrap_item_id' => 'required|exists:scrap_items,id',
            'items.*.gross_weight' => 'required|numeric|gt:0',
            'items.*.tare_weight' => 'nullable|numeric|min:0',
            'items.*.price_per_kg' => 'nullable|numeric|gt:0',
            'items.*.notes' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Daftar timbangan rongsok tidak boleh kosong.',
            'items.min' => 'Minimal harus menimbang 1 jenis barang rongsok.',
            'items.*.scrap_item_id.required' => 'Jenis barang rongsok wajib dipilih.',
            'items.*.scrap_item_id.exists' => 'Barang rongsok yang dipilih tidak valid.',
            'items.*.gross_weight.required' => 'Berat kotor timbangan wajib diisi.',
            'items.*.gross_weight.gt' => 'Berat kotor timbangan harus lebih dari 0 kg.',
        ];
    }
}

