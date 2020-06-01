<?php

namespace App\Http\Requests\Api;

use App\Models\Product;

class AddCartRequest extends FormRequest
{
    public function rules()
    {
        return [
            'id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!$product = Product::query()->find($value)) {
                        return $fail('该商品不存在');
                    }
                    if (!$product->status) {
                        return $fail('该商品未上架');
                    }
                    if ($product->stock === 0) {
                        return $fail('该商品已售完');
                    }
                    if ($this->input('amount') > 0 && $product->stock < $this->input('amount')) {
                        return $fail('该商品库存不足');
                    }
                },
            ],
            'amount' => ['integer'],
        ];
    }

    public function attributes()
    {
        return [
            'amount' => '商品数量'
        ];
    }

    public function messages()
    {
        return [
            'id.required' => '请选择商品'
        ];
    }
}