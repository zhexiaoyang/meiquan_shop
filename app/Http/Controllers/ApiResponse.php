<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Response;

trait ApiResponse
{

    /**
     * @param $data
     * @param $code
     * @param array $header
     * @return mixed
     */
    public function respond($data, $code, $header = [])
    {

        return Response::json($data, $code, $header);
    }

    /**
     * @param null $data
     * @param string $message
     * @param int $code
     * @param int $http_code
     * @return mixed
     */
    public function status($data = null, $message = "成功", $code = 0, $http_code = 200)
    {

        $response = [
            'code' => $code,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return $this->respond($response, $http_code);

    }

    /**
     * @param array $data
     * @param string $message
     * @param int $code
     * @param int $http_code
     * @return mixed
     */
    public function success($data = [], $message = "成功", $code = 0, $http_code = 200)
    {

        return $this->status($data, $message, $code, $http_code);

    }

    /**
     * @param array $data
     * @param string $message
     * @param int $code
     * @param int $http_code
     * @return mixed
     */
    public function page(LengthAwarePaginator $data, $message = "成功", $code = 0, $http_code = 200)
    {

        $result = [
            'total' => $data->total(),
            'total_page' => $data->lastPage(),
            'list' => $data->items()
        ];

        return $this->status($result, $message, $code, $http_code);

    }

    /**
     * @param string $message
     * @param int $code
     * @param int $http_code
     * @return mixed
     */
    public function message($message = "成功", $code = 0, $http_code = 200)
    {

        return $this->status([], $message, $code, $http_code);

    }

    /**
     * @param $message
     * @param $code
     * @param int $http_code
     * @return mixed
     */
    public function error($message, $code=100, $http_code = 200)
    {
        return $this->status(null, $message, $code, $http_code);
    }

}