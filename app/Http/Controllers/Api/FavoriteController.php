<?php

namespace App\Http\Controllers\Api;

use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{

    public function index(Request $request)
    {
        $list = [];

        $products = $request->user()->favorites()->with(['category' => function($query) {
            $query->select('id', 'title');
        }])->paginate();

        if (!empty($products)) {
            foreach ($products as $product) {
                $tmp['id'] = $product->id;
                $tmp['name'] = $product->name;
                $tmp['cover'] = $product->cover;
                $tmp['spec'] = $product->spec;
                $tmp['price'] = $product->price;
                $tmp['sale_count'] = $product->sale_count;
                $tmp['syz'] = $product->syz;
                $tmp['category'] = $product->category;
                $list[] = $tmp;
            }
        }

        $result = [
            'total' => $products->total(),
            'total_page' => $products->lastPage(),
            'list' => $list
        ];

        return $this->success($result);
    }

    public function store(Request $request)
    {
        if (!$product = Product::query()->find($request->get('product_id', 0))) {
            return $this->error('商品不存在');
        }

        if (!Favorite::query()->where(['user_id' => $request->user()->id, 'product_id' => $product->id])->first()) {
            $request->user()->favorites()->attach($product->id);
        }

        return $this->message();
    }

    public function destroy(Product $product, Request $request)
    {
        $request->user()->favorites()->detach($product->id);

        return $this->message();
    }

}