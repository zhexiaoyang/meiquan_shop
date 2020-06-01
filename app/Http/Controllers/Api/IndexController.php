<?php

namespace App\Http\Controllers\Api;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function index()
    {
        $categories = Category::query()->select('id', 'title', 'image')->where(['parent_id' => 0, 'status' => 1])
            ->orderBy('sort', 'desc')->get();

        $banners = Banner::query()->where('status', 1)->get();

        $seckill = Product::query()->limit(10)->get();

        $sales = Product::query()->select('id', 'name', 'cover', 'price', 'sale_count', 'syz')
            ->orderBy('sale_count', 'desc')->orderBy('sort', 'desc')->limit(6)->get();

        $recommend = Product::query()->select('id', 'name', 'cover', 'price', 'sale_count', 'syz')
            ->where('is_recommend', 1)->orderBy('sort', 'desc')->limit(6)->get();


        return $this->success(compact(['categories', 'banners', 'seckill', 'sales', 'recommend']));
    }
}
