<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'buyer_name' => 'nullable|string|max:150',
            'buyer_phone' => 'nullable|string|max:30',
            'buyer_address' => 'nullable|string|max:255',
            'delivery_type' => 'nullable|string|in:pickup,delivery',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'bank_info' => 'nullable|string|max:255',
            'custom_bank' => 'nullable|string|max:100',
            'custom_account_name' => 'nullable|string|max:150',
            'custom_account_no' => 'nullable|string|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'payment_status' => 'nullable|string|in:paid,unpaid,partial',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.sell_price_per_kg' => 'required|numeric|gt:0',
            'items.*.notes' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'buyer_name.required' => 'Nama pembeli / pabrik rongsok wajib diisi.',
            'items.required' => 'Daftar barang penjualan wajib ada.',
            'items.*.sell_price_per_kg.required' => 'Harga jual per kg / satuan wajib diisi.',
            'items.*.sell_price_per_kg.gt' => 'Harga jual per kg / satuan harus lebih dari Rp 0.',
        ];
    }
}
