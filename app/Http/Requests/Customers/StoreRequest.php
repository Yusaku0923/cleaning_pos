<?php

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string',
            'name_kana' => 'required|regex:/^[ァ-ヴーｦ-ﾟ 　]+$/u',
            'phone_number' => 'nullable|numeric|digits_between:6,11',
            'birth_day' => '',
            'sex' => '',
        ];
    }
}
