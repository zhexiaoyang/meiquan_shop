<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class FormRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function failedValidation($validator)
    {
        $error = $validator->errors()->first();
        // $allErrors = $validator->errors()->all(); 所有错误

        $response = response()->json([
            'code' => 422,
            'message' => $error,
        ]);

        throw new HttpResponseException($response);
    }
}
