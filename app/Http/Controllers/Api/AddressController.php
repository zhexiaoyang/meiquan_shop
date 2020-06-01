<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AddressRequest;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $list = $request->user()->address()->orderBy("id", "desc")->get();

        return $this->success($list);
    }

    public function show(Address $address, Request $request)
    {
        if ($request->user()->id === $address->user_id) {
            return $this->success($address);
        }

        return $this->error("地址不存在", 404);
    }

    public function store(AddressRequest $request)
    {
        if ($request->is_default == 1) {
            $request->user()->address()->update(["is_default" => 0]);
        }

        $request->user()->address()->create($request->only([
            'name',
            'tel',
            'province',
            'city',
            'county',
            'address_detail',
            'area_code',
            'postal_code',
            'is_default',
        ]));

        return $this->message("添加成功");
    }

    public function update(Address $address, AddressRequest $request)
    {
        if ($request->user()->id === $address->user_id) {

            if ($request->is_default == 1) {
                $request->user()->address()->update(["is_default" => 0]);
            }

            $address->name = $request->get('name', '');
            $address->tel = $request->get('tel', '');
            $address->province = $request->get('province', '');
            $address->city = $request->get('city', '');
            $address->county = $request->get('county', '');
            $address->address_detail = $request->get('address_detail', '');
            $address->area_code = $request->get('area_code', '');
            $address->postal_code = $request->get('postal_code', '');
            $address->is_default = $request->get('is_default', '');

            $address->save();

        }

        return $this->message("修改成功");
    }

    public function default(Address $address, Request $request)
    {
        if ($request->user()->id === $address->user_id) {

            $request->user()->address()->update(["is_default" => 0]);

            $address->is_default = 1;

            $address->save();

        }

        return $this->message("修改成功");
    }

    public function destroy(Address $address, Request $request)
    {
        if ($request->user()->id === $address->user_id) {
            $address->delete();
        }

        return $this->message("删除成功");
    }

}
