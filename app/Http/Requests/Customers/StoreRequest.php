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
            'name_kana' => 'required',
            'phone_number' => 'nullable|numeric|digits_between:6,11',
            'birth_day' => '',
            'sex' => '',
        ];
    }

    public function messages()
    {
        return [
            '*.required' => ':attributeを入力して下さい。',
            'name_kana.max' => ':attributeはカタカナ（全角・半角）で入力してください。',
            'phone_number.numeric' => ':attributeは数字で入力してください。',
            'phone_number.digits_between' => ':attributeは6文字以上11文字以下で入力してください。',
        ];
    }

    public function attributes()
    {
        return [
            'name' => '名前',
            'name_kana' => '名前（カナ）',
            'phone_number' => '電話番号',
            'birth_day' => '生年月日',
            'sex' => '性別',
        ];
    }
}
