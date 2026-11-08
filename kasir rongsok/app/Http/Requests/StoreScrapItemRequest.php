<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScrapItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemId = $this->route('scrap_item') ? $this->route('scrap_item')->id : null;

        return [
            'name' => 'required|string|max:150',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('scrap_items', 'code')->ignore($itemId),
            ],
            'category_id' => 'required|exists:item_categories,id',
            'unit' => 'required|string|max:20',
            'buy_price_per_kg' => 'required|numeric|min:0',
            'sell_price_per_kg' => 'nullable|numeric|min:0',
            'min_stock_alert' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'price_change_reason' => 'nullable|string|max:255',
        ];
    }

    protected function prepareForValidation(): void
    {
        $cleanNum = function ($val) {
            if ($val === '-' || $val === '' || is_null($val)) return 0;
            return (float) preg_replace('/[^\d]/', '', (string) $val);
        };

        $merge = [];
        if ($this->has('buy_price_per_kg')) {
            $merge['buy_price_per_kg'] = $cleanNum($this->buy_price_per_kg);
        }
        if ($this->has('sell_price_per_kg')) {
            $merge['sell_price_per_kg'] = $cleanNum($this->sell_price_per_kg);
        }
        if (!empty($merge)) {
            $this->merge($merge);
        }
    }
}
