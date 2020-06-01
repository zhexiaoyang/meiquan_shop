<?php

use Illuminate\Support\Facades\Route;


Route::prefix('admin')->namespace("Admin")->group(function () {
    Route::post('auth/login', 'AuthController@login');
    Route::middleware(['auth:admin', 'scope:admin'])->group(function () {
        Route::get('user', 'AuthController@user');
    });
});


Route::namespace("Api")->group(function () {
    /**
     * 无需登录
     */
    // 登录
    Route::post('auth/login', 'AuthController@login');
    // 首页
    Route::get('/index', 'IndexController@index')->name('api.index.index');
    // 分类管理
    Route::get('/category', 'CategoryController@index')->name('api.category.index');
    // 商品管理
    Route::resource('product', 'ProductController', ['only' => ['index', 'show']]);
    // 图片验证码
    Route::get('captcha', 'CaptchaController@show')->name('api.captcha.show');
    // 短信验证码
    Route::post('verificationCodes', 'VerificationCodesController@store')
        ->name('api.verificationCodes.store');
    /**
     * 需要登录
     */
    Route::middleware(['auth:api', 'scope:user'])->group(function () {
        Route::get('user', 'AuthController@user');
        // 个人信息
        Route::get('me', 'AuthController@me');
        // 购物车管理
        Route::resource('cart', 'CartController', ['only' => ['index', 'store', 'destroy', 'update']]);
        // 设置默认地址
        Route::put('address/{address}/default', 'AddressController@default');
        // 地址管理
        Route::resource('address', 'AddressController', ['only' => ['index', 'show', 'store', 'update', 'destroy']]);
        // 确认订单
        Route::get('confirm_order', 'OrderController@confirmOrder');
        // 订单管理
        Route::resource('order', 'OrderController', ['only' => ['index', 'show', 'store', 'update', 'destroy']]);
        // 付款
        Route::get('order_money', 'OrderController@money');
        // 付款
        Route::get('order_pay', 'OrderController@pay');
        // 收藏
        Route::get('favorite', 'FavoriteController@index');
        Route::post('favorite', 'FavoriteController@store');
        Route::delete('favorite', 'FavoriteController@destroy');
        // Route::resource('favorite', 'FavoriteController', ['only' => ['index', 'store', 'destroy']]);
        // 浏览历史记录
        Route::get('history', 'HistoryController@index');
    });
});

