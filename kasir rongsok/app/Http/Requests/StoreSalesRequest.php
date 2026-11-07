<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isKasir = $this->user()?->isKasir() || $this->boolean('is_draft');

        return [
            'buyer_name' => 'required|string|max:150',
            'buyer_phone' => 'nullable|string|max:30',
            'buyer_address' => 'nullable|string|max:255',
            'delivery_type' => 'nullable|string|in:pickup,delivery',
            'delivery_order_id' => 'nullable|exists:delivery_orders,id',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:30',
            'driver_id' => 'nullable|exists:drivers,id',
            'driver_bank_name' => 'nullable|string|max:100',
            'driver_bank_account_number' => 'nullable|string|max:60',
            'driver_bank_account_name' => 'nullable|string|max:150',
            'save_to_driver_bank' => 'nullable|boolean',
            'vehicle_number' => 'nullable|string|max:20',
            'vehicle_type' => 'nullable|string|max:50',
            'payment_method_id' => 'nullable',
            'new_payment_method_name' => 'nullable|string|max:100',
            'bank_info' => 'nullable|string|max:255',
            'custom_bank' => 'nullable|string|max:100',
            'custom_account_name' => 'nullable|string|max:150',
            'custom_account_no' => 'nullable|string|max:100',
            'save_to_user_bank' => 'nullable|boolean',
            'transaction_date' => 'nullable|date',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.scrap_item_id' => 'required|exists:scrap_items,id',
            'items.*.item_price_stock_id' => 'nullable|integer',
            'items.*.tier_id' => 'nullable|string',
            'items.*.buy_price_snapshot' => 'nullable|numeric|min:0',
            'items.*.unit_type' => 'nullable|string|in:kg,pcs,satuan,unit',
            'items.*.item_type_name' => 'nullable|string|max:150',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.sell_price_per_kg' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
            'shipping_cost' => 'nullable',
            'loading_cost' => 'nullable',
        ];
    }

    protected function prepareForValidation(): void
    {
        $sanitizeCost = function ($val) {
            if ($val === '-' || $val === '' || is_null($val)) return 0;
            return (float) preg_replace('/[^\d.]/', '', (string) $val);
        };

        $merge = [];
        if ($this->has('shipping_cost')) {
            $merge['shipping_cost'] = $sanitizeCost($this->shipping_cost);
        }
        if ($this->has('loading_cost')) {
            $merge['loading_cost'] = $sanitizeCost($this->loading_cost);
        }
        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function withValidator($validator): void
    {
        // Items weight and sell_price can be empty / 0 as per user requirement
    }

    public function messages(): array
    {
        return [
            'buyer_name.required' => 'Nama pembeli / pabrik rongsok wajib diisi.',
            'items.required' => 'Daftar barang yang dijual wajib diisi.',
            'items.*.scrap_item_id.required' => 'Pilih jenis barang rongsok yang dijual.',
            'items.*.weight.gt' => 'Berat barang yang dijual harus lebih dari 0 kg.',
            'items.*.sell_price_per_kg.gt' => 'Harga jual per kg harus lebih dari Rp 0.',
        ];
    }
}

