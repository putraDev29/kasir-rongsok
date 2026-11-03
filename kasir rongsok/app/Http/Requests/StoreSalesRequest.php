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
        return [
            'buyer_name' => 'required|string|max:150',
            'buyer_phone' => 'nullable|string|max:30',
            'buyer_address' => 'nullable|string|max:255',
            'delivery_order_id' => 'nullable|exists:delivery_orders,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'bank_info' => 'nullable|string|max:255',
            'custom_bank' => 'nullable|string|max:100',
            'custom_account_name' => 'nullable|string|max:150',
            'custom_account_no' => 'nullable|string|max:100',
            'transaction_date' => 'nullable|date',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.scrap_item_id' => 'required|exists:scrap_items,id',
            'items.*.unit_type' => 'nullable|string|in:kg,pcs,satuan,unit',
            'items.*.item_type_name' => 'nullable|string|max:150',
            'items.*.quantity' => 'nullable|numeric|min:0.01',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.sell_price_per_kg' => 'required|numeric|gt:0',
            'items.*.notes' => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            if (!is_array($items) || empty($items)) {
                return;
            }

            foreach ($items as $index => $item) {
                $unitType = strtolower($item['unit_type'] ?? 'kg');
                if (in_array($unitType, ['pcs', 'satuan', 'unit'])) {
                    $qty = (float) ($item['quantity'] ?? 0);
                    if ($qty <= 0) {
                        $validator->errors()->add("items.{$index}.quantity", "Jumlah barang jual (pcs/satuan) harus lebih dari 0.");
                    }
                } else {
                    $weight = (float) ($item['weight'] ?? 0);
                    if ($weight <= 0) {
                        $validator->errors()->add("items.{$index}.weight", "Berat barang yang dijual harus lebih dari 0 kg.");
                    }
                }
            }
        });
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

