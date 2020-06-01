<?php

namespace App\Http\Requests\Api;

class AddressRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name'          => 'required',
            'tel'           => 'required',
            'province'      => 'required',
            'city'          => 'required',
            'county'        => 'required',
            'address_detail'=> 'required',
            'area_code'      => 'required',
            'postal_code'    => 'required',
        ];
    }

    public function attributes()
    {
        return [
            'name'          => '收货人',
            'tel'           => '电话',
            'province'      => '省',
            'city'          => '市',
            'county'        => '县',
            'address_detail'=> '详细地址',
            'area_code'      => '地区编码',
            'postal_code'    => '邮编',
        ];
    }
}
