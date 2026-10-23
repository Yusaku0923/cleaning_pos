<?php

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;

/** 旧 App\Http\Requests\Customers\StoreRequest と同じ規則・文言。 */
class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'name_kana' => 'required',
            'phone_number' => 'nullable|numeric|digits_between:6,11',
            'cutoff_date' => 'nullable|integer|between:0,99',
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => ':attributeを入力して下さい。',
            'name_kana.max' => ':attributeはカタカナ（全角・半角）で入力してください。',
            'phone_number.numeric' => ':attributeは数字で入力してください。',
            'phone_number.digits_between' => ':attributeは6文字以上11文字以下で入力してください。',
        ];
    }

    public function attributes(): array
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
