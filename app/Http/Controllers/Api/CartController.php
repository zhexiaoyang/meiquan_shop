<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AddCartRequest;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CartController extends Controller
{

    public function index(Request $request)
    {
        $list = $request->user()->cart()->select("id", "product_id", "checked", "amount")->with(['product' => function($query) {
            $query->select("id", "name", "cover", "syz", "stock", "spec", "price");
        }])->orderBy('created_at', 'desc')->get();

        return $this->success($list);
    }

    public function store(AddCartRequest $request)
    {
        $user = Auth::user();
        $amount = $request->input('amount', 1);
        $product_id = $request->input('id');

        // 从数据库中查询该商品是否已经在购物车中
        if ($cart = $user->cart()->where('product_id', $product_id)->first()) {

            // 如果存在则直接叠加商品数量
            $cart->update([
                'amount' => $cart->amount + $amount,
            ]);
        } else {
            // 否则创建一个新的购物车记录
            $cart = new Cart(['amount' => $amount]);
            $cart->user()->associate($user);
            $cart->product()->associate($product_id);
            $cart->save();
        }

        return $this->message("添加成功");
    }

    public function destroy(Cart $cart, Request $request)
    {
        $cart->delete();

        return $this->message("删除成功");
    }

    public function update(Cart $cart, Request $request)
    {
        $amount = $request->post('amount', '');
        $checked = $request->post('checked', '');
        if ($amount) {
            if (!$amount || $amount < 1) {
                return $this->error($amount);
            }

            if ($cart->product->stock < $amount) {
                return $this->error("该商品库存不足");
            }
            $cart->amount = $amount;
        }

        if ($checked === true || $checked === false) {
            $cart->checked = $checked;
        }
        $cart->save();
        return $this->message("修改成功");
    }
}
