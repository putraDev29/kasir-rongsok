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
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'bank_info' => 'nullable|string|max:255',
            'custom_bank' => 'nullable|string|max:100',
            'custom_account_name' => 'nullable|string|max:150',
            'custom_account_no' => 'nullable|string|max:100',
            'save_to_user_bank' => 'nullable|boolean',
            'transaction_date' => 'nullable|date',
            'discount_amount' => 'nullable|numeric|min:0',
            'payment_status' => 'nullable|string|in:paid,unpaid,partial',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.scrap_item_id' => 'nullable|exists:scrap_items,id',
            'items.*.item_price_stock_id' => 'nullable|integer',
            'items.*.tier_id' => 'nullable|string',
            'items.*.buy_price_snapshot' => 'nullable|numeric|min:0',
            'items.*.unit_type' => 'nullable|string|in:kg,pcs,satuan,unit',
            'items.*.item_type_name' => 'nullable|string|max:150',
            'items.*.quantity' => 'nullable|numeric|min:0.01',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.sell_price_per_kg' => 'required|numeric|gt:0',
            'items.*.package_qty' => 'nullable|integer|min:0',
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
