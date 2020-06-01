<?php

namespace App\Http\Controllers\Api;

use App\Models\History;
use Illuminate\Http\Request;

class HistoryController extends Controller
{

    public function index(Request $request)
    {
        $result = [];

        $data = History::with('product')->where([
            'user_id' => $request->user()->id
        ])->orderBy('updated_at', 'desc')->limit(10)->get();


        if (!empty($data)) {
            foreach ($data as $v) {
                $tmp['id'] = $v->product->id;
                // $tmp['name'] = $product->name;
                $tmp['cover'] = $v->product->cover;
                // $tmp['spec'] = $product->spec;
                // $tmp['price'] = $product->price;
                // $tmp['sale_count'] = $product->sale_count;
                // $tmp['syz'] = $product->syz;
                // $tmp['category'] = $product->category;
                $result[] = $tmp;
            }
        }

        return $this->success($result);
    }

}