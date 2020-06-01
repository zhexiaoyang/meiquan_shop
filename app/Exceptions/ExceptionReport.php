<?php

namespace App\Exceptions;

use App\Http\Controllers\ApiResponse;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Exceptions\MissingScopeException;
use Spatie\Permission\Exceptions\PermissionAlreadyExists;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class ExceptionReport
{
    use ApiResponse;

    /**
     * @var Exception
     */
    public $exception;
    /**
     * @var Request
     */
    public $request;

    /**
     * @var
     */
    protected $report;

    /**
     * @var
     */
    protected $message;

    /**
     * ExceptionReport constructor.
     * @param Request $request
     * @param Exception $exception
     */
    function __construct(Request $request, Exception $exception)
    {
        $this->request = $request;
        $this->exception = $exception;
    }

    /**
     * @var array
     */
    public $doReport = [
        AuthenticationException::class => ['未授权', 401],
        ModelNotFoundException::class => ['请求不存在', 404],
        MissingScopeException::class => ['非法请求', 400],
        UnauthorizedException::class => ['没有权限', 403],
        ValidationException::class => ['参数错误', 422],
        PermissionAlreadyExists::class => ['权限已存在', 422],
        RoleAlreadyExists::class => ['角色已存在', 422],
        MethodNotAllowedHttpException::class => ['请求方法错误', 0],
        QueryException::class => ['查询失败', 600],
        Exception::class => ['系统错误', 500]
    ];

    /**
     * @return bool
     */
    public function shouldReturn(){

        if (! ($this->request->wantsJson() || $this->request->ajax())){
            return false;
        }

        foreach (array_keys($this->doReport) as $report){

            if ($this->exception instanceof $report){
                if($this->exception instanceof ValidationException){
                    $this->message = array_values($this->exception->errors())[0][0];
                }

                $this->report = $report;
                return true;
            }
        }

        return false;

    }

    /**
     * @param Exception $e
     * @return static
     */
    public static function make(Exception $e){

        return new static(\request(),$e);
    }

    /**
     * @return mixed
     */
    public function report(){

        $message = $this->doReport[$this->report];

        return $this->error($this->message ?? $message[0],$message[1]);

    }

}