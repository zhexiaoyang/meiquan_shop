<?php

namespace App\Http\Controllers\Api;

use App\Models\Favorite;
use App\Models\History;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->select('id','category_id','cover','name','spec','price','sale_count','syz')
            ->with(['category' => function($query) {
            $query->select('id', 'title');
        }]);

        if ($category = $request->get('category')) {
            $query->where('category_id', $category);
        }

        if ($order_by = $request->get('order_by')) {
            $sort = $request->get('sort', 'asc');
            $query->orderBy($order_by, $sort);
        }

        $products = $query->paginate(20);

        return $this->page($products);
    }

    public function show(Product $product)
    {
        $result = [];

        if ($product) {

            if (auth('api')->user()) {
                if ($history = History::query()->where(['user_id' => auth('api')->user()->id, 'product_id' => $product->id])->first()) {
                    $history->updated_at = date("Y-m-d H:i:s");
                    $history->save();
                } else {
                    auth('api')->user()->histories()->attach($product->id);
                }
            }

            $related = Product::query()->select('id','cover','name','spec','price','sale_count','syz')
                ->where('category_id', $product->category_id)->where('id', '<>', $product->id)
                ->orderBy('sale_count','desc')->limit(10)->get();

            $favorite = 0;

            if ($user = Auth::guard('api')->user()) {
                $favorite = Favorite::query()->where(['user_id' => $user->id, 'product_id' => $product->id])->first() ? 1 : 0;
            }

            $result['goods'] = [
                'id' => $product->id,
                'images' => explode(',', $product->images),
                'name' => $product->name,
                'spec' => $product->spec,
                'price' => $product->price,
                'sale_count' => $product->sale_count,
                'syz' => $product->syz,
                'related' => $related,
                'favorite' => $favorite
            ];

            $result['detail'] = [
                'spec' => $product->spec,
                'name' => $product->name,
                'upc' => $product->upc,
                'approval' => $product->approval,
                'generi_name' => $product->generi_name,
                'manufacturer' => $product->manufacturer,
                'brand' => $product->brand,
                'term_of_validity' => $product->term_of_validity,
                'status' => $product->status,
                'yfyl' => $product->yfyl,
                'syz' => $product->syz,
                'syrq' => $product->syrq,
                'cf' => $product->cf,
                'blfy' => $product->blfy,
                'jj' => $product->jj,
                'zysx' => $product->zysx,
                'ypxhzy' => $product->ypxhzy,
                'xz' => $product->xz,
                'bz' => $product->bz,
                'jx' => $product->jx,
                'zc' => $product->zc,
            ];
        }

        return $this->success($result);
    }
}
