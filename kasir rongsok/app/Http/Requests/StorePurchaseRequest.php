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
            'supplier_id' => 'nullable',
            'supplier_name' => 'nullable|string|max:150',
            'supplier_phone' => 'nullable|string|max:30',
            'supplier_address' => 'nullable|string|max:255',
            'save_as_supplier' => 'nullable|boolean',
            'payment_method_id' => 'nullable',
            'new_payment_method_name' => 'nullable|string|max:100',
            'bank_info' => 'nullable|string|max:255',
            'custom_bank' => 'nullable|string|max:100',
            'custom_account_name' => 'nullable|string|max:150',
            'custom_account_no' => 'nullable|string|max:100',
            'save_to_supplier_bank' => 'nullable|boolean',
            'save_to_user_bank' => 'nullable|boolean',
            'payment_notes' => 'nullable|string|max:255',
            'transaction_date' => 'nullable|date',
            'discount_amount' => 'nullable|numeric|min:0',
            'rounding_amount' => 'nullable|numeric',
            'debt_deduction_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.scrap_item_id' => 'nullable',
            'items.*.new_item_name' => 'nullable|string|max:150',
            'items.*.search_text' => 'nullable|string|max:150',
            'items.*.category_id' => 'nullable',
            'items.*.new_category_name' => 'nullable|string|max:150',
            'items.*.unit_type' => 'nullable|string|in:kg,pcs,satuan,unit',
            'items.*.item_type_name' => 'nullable|string|max:150',
            'items.*.quantity' => 'nullable|numeric|min:0.01',
            'items.*.gross_weight' => 'nullable|numeric|min:0',
            'items.*.tare_weight' => 'nullable|numeric|min:0',
            'items.*.tare_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tare_mode' => 'nullable|string|max:20',
            'items.*.price_per_kg' => 'nullable|numeric|gt:0',
            'items.*.notes' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Daftar timbangan rongsok tidak boleh kosong.',
            'items.min' => 'Minimal harus menimbang 1 jenis barang rongsok.',
            'items.*.scrap_item_id.required' => 'Jenis barang rongsok wajib dipilih atau diinput.',
            'items.*.price_per_kg.gt' => 'Harga beli barang rongsok harus lebih dari Rp 0.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            if (!is_array($items) || empty($items)) {
                return;
            }

            $scrapItemIds = array_filter(array_column($items, 'scrap_item_id'), fn($id) => is_numeric($id) && $id > 0);
            $scrapItems = !empty($scrapItemIds)
                ? \App\Models\ScrapItem::whereIn('id', $scrapItemIds)->get()->keyBy('id')
                : collect();

            foreach ($items as $index => $item) {
                $scrapItemId = $item['scrap_item_id'] ?? null;
                $newItemName = trim($item['new_item_name'] ?? $item['search_text'] ?? '');

                if ((empty($scrapItemId) || $scrapItemId === '__new__') && empty($newItemName)) {
                    $validator->errors()->add("items.{$index}.scrap_item_id", "Pilih barang rongsok atau ketik nama barang baru pada baris ke-" . ($index + 1) . ".");
                } elseif (!empty($scrapItemId) && $scrapItemId !== '__new__') {
                    if (is_numeric($scrapItemId) && !isset($scrapItems[$scrapItemId])) {
                        $validator->errors()->add("items.{$index}.scrap_item_id", "Barang rongsok yang dipilih pada baris ke-" . ($index + 1) . " tidak valid.");
                    }
                }

                $unitType = strtolower($item['unit_type'] ?? 'kg');
                if (in_array($unitType, ['pcs', 'satuan', 'unit'])) {
                    $qty = (float) ($item['quantity'] ?? 0);
                    if ($qty <= 0) {
                        $validator->errors()->add("items.{$index}.quantity", "Jumlah barang (pcs/satuan) harus lebih dari 0.");
                    }
                } else {
                    $gross = (float) ($item['gross_weight'] ?? 0);
                    if ($gross <= 0) {
                        $validator->errors()->add("items.{$index}.gross_weight", "Berat kotor timbangan harus lebih dari 0 kg.");
                    }
                }

            }
        });
    }
}

