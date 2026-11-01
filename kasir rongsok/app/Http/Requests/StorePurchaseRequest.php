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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            if (!is_array($items) || empty($items)) {
                return;
            }

            $scrapItemIds = array_filter(array_column($items, 'scrap_item_id'));
            if (empty($scrapItemIds)) {
                return;
            }

            $scrapItems = \App\Models\ScrapItem::whereIn('id', $scrapItemIds)->get()->keyBy('id');

            foreach ($items as $index => $item) {
                $scrapItemId = $item['scrap_item_id'] ?? null;
                $pricePerKg = isset($item['price_per_kg']) && is_numeric($item['price_per_kg'])
                    ? (float) $item['price_per_kg']
                    : null;

                if ($scrapItemId && $pricePerKg !== null && isset($scrapItems[$scrapItemId])) {
                    $masterItem = $scrapItems[$scrapItemId];
                    $masterBuyPrice = (float) $masterItem->buy_price_per_kg;

                    if ($masterBuyPrice > 0 && $pricePerKg > $masterBuyPrice) {
                        $validator->errors()->add(
                            "items.{$index}.price_per_kg",
                            "Harga beli untuk {$masterItem->name} (Rp " . number_format($pricePerKg, 0, ',', '.') . ") melebihi harga master (Rp " . number_format($masterBuyPrice, 0, ',', '.') . "). Anda hanya dapat menginput harga sama atau di bawahnya."
                        );
                    }
                }
            }
        });
    }
}

