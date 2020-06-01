<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidRequestException;
use App\Http\Requests\Api\OrderRequest;
use App\Jobs\CloseOrder;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yansongda\LaravelPay\Facades\Pay;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $all = $request->get('all', 1);
        $status = $request->get('status', '');
        $orders = [];
        if ($status == 'current') {
            $orders = Order::select('id', 'no', 'total_amount', 'closed', 'reviewed', 'ship_status', 'paid_at', 'created_at')
                ->with(['items.product' => function($query) {
                    $query->select('id','name', 'spec', 'cover');
                }])
                ->where('user_id', $request->user()->id)
                ->where('closed', 0)
                ->where('refund_status', '<', 2)
                ->orderBy('created_at', 'desc')
                ->paginate();
        } else {
            $query = Order::select('id', 'no', 'total_amount', 'closed', 'reviewed', 'ship_status', 'paid_at', 'created_at')
                ->with(['items.product' => function($query) {
                    $query->select('id','name', 'spec', 'cover');
                }])
                ->where('user_id', $request->user()->id);

            if ($status == 'pending_payment') {
                $query->where('closed', 0)->where('payment_method', 0);
            }

            if ($status == 'pending_ship') {
                $query->where('closed', 0)->where('payment_method', '>', 0)->where('payment_method', 0);
            }

            if ($status == 'pending_received') {
                $query->where('closed', 0)->where('payment_method', '>', 0)->where('payment_method', 1);
            }

            if ($status == 'pending_evaluate') {
                $query->where('closed', 0)->where('payment_method', '>', 0)->where('payment_method', 2);
            }

            $orders = $query->orderBy('created_at', 'desc')->paginate();
        }

        return $this->success($orders);
    }

    public function show(Order $order, Request $request)
    {
        $data = $order->load(['items.product' => function($query) {
            $query->select("id", "name", "cover", "syz", "stock", "spec", "price");
        }]);

        return $this->success(compact(["data"]));
    }

    public function confirmOrder(Request $request)
    {
        $address = $request->user()->address()->where("is_default", 1)->first();

        if ($product = Product::query()->find($request->get('product_id', 0))) {
            if (!$request->user()->cart()->where('product_id', $product->id)->first()) {
                $cart = new Cart(['amount' => 1]);
                $cart->user()->associate($request->user());
                $cart->product()->associate($product);
                $cart->save();
            }
            $request->user()->cart()->where('product_id', '<>', $product->id)->update(['checked' => 0]);
        }

        $products = $request->user()->cart()->select("id", "product_id", "checked", "amount")->with(['product' => function($query) {
            $query->select("id", "name", "cover", "syz", "stock", "spec", "price");
        }])->where("checked", 1)->get();

        $product_money = 0;
        if ($products->count()) {
            foreach ($products as $cat) {
                $product_money += $cat->product->price * $cat->amount;
            }
        } else {
            return $this->error("请选择商品", 2);
        }

        $money['product'] = $product_money;
        $money['freight'] = $product_money > 28 ? 0 : 5;
        $money['discount'] = 0;
        $money['total'] = $money['product'] + $money['freight'] - $money['discount'];

        return $this->success(compact(["address", "products", "money"]));
    }


    public function store(OrderRequest $request)
    {
        $user  = $request->user();
        // 开启一个数据库事务
        $order = \DB::transaction(function () use ($user, $request) {
            $address = Address::find($request->input('address_id'));
            // 更新此地址的最后使用时间
            $address->update(['last_used_at' => Carbon::now()]);
            // 创建一个订单
            $order   = new Order([
                'address'      => [ // 将地址信息放入订单中
                    'address'       => $address->full_address,
                    'postalCode'    => $address->postalCode,
                    'name'          => $address->name,
                    'tel'           => $address->tel,
                ],
                'remark'       => $request->input('remark'),
                'total_amount' => 0,
            ]);
            // 订单关联到当前用户
            $order->user()->associate($user);
            // 写入数据库
            $order->save();

            $totalAmount = 0;
            $originalAmount = 0;
            $items       = $request->input('items');
            // 遍历用户提交的 SKU
            foreach ($items as $data) {
                $product  = Product::find($data['id']);
                // 创建一个 OrderItem 并直接与当前订单关联
                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price'  => $product->price,
                ]);
                $item->product()->associate($product->id);
                $item->save();
                $totalAmount += $product->price * $data['amount'];
                $originalAmount += $product->price * $data['amount'];
                if ($product->decreaseStock($data['amount']) <= 0) {
                    throw new InvalidRequestException('该商品库存不足');
                }
            }

            $totalAmount = $totalAmount === 0 ? 0.01 : $totalAmount;
            $originalAmount = $originalAmount === 0 ? 0.01 : $originalAmount;
            $shipping_fee = $originalAmount > 28 ? 0 : 5;
            $originalAmount += $shipping_fee;

            // 更新订单总金额
            $order->update(['total_amount' => $totalAmount, 'original_amount' => $originalAmount, 'shipping_fee' => $shipping_fee]);

            // 将下单的商品从购物车中移除
            $product_ids = collect($items)->pluck('id');
            $user->cart()->whereIn('product_id', $product_ids)->delete();

            return $order;
        });

        dispatch(new CloseOrder($order, config('app.order_ttl')));

        return $this->success($order);
    }

    public function money(Request $request)
    {
        $no = $request->get("order_id", 0);
        if (!$no) {
            return $this->success(['money' => -1]);
        }

        $order = Order::query()->where("no", $no)->first();

        if ($order->paid_at || $order->closed) {
            return $this->success(['money' => 0]);
        }

        return $this->success(['money' => $order->total_amount]);
    }

    public function pay(Request $request)
    {
        $no = $request->get("order_id", 0);
        if (!$no) {
            return $this->success(['money' => -1]);
        }

        $order = Order::query()->where("no", $no)->first();

        if ($order->paid_at || $order->closed) {
            return $this->error('订单已支付');
        }

        $order = [
            'out_trade_no'  => $order->no,
            'body'          => '美全配送充值',
            'total_fee'     => $order->total_amount * 100
        ];

        $wap = Pay::wechat()->wap($order);

        \Log::info('$wechatOrder', [$wap->getContent()]);

        $data = [
            'mweb_url' => $wap->getTargetUrl(),
            'content' => $wap->getContent(),
        ];

        return $this->success($data);
    }
}
