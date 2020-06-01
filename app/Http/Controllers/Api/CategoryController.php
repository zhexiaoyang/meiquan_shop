<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $result = [];

        $category = Category::query()->select('id','parent_id','title','image')->where('status', 1)
            ->orderBy('parent_id')->orderBy('sort', 'desc')->get()->toArray();

        if (!empty($category)) {
            foreach ($category as $k => $v) {
                if ($v['parent_id'] == 0) {
                    $result[$v['id']] = $v;
                    $result[$v['id']]['children'] = [];
                } else {
                    if (isset($result[$v['parent_id']])) {
                        $result[$v['parent_id']]['children'][] = $v;
                    }
                }
            }
        }

        return $this->success(array_values($result));
    }
}
