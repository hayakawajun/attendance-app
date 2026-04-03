<?php

namespace App\Http\Requests;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginRequest extends FortifyLoginRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
/*    public function rules()
    {
        return [
            'email' => 'required | string | email | max:255',
            'password' => 'required | string | min:8 | max:255'
        ];
    }
*/
    public function rules()
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                function($attribute, $value, $fail){
                    $guard = ($this->is('admin/*') || $this->is('admin/login'))
                    ? 'admin' : 'web';
                    $model = ($guard === 'admin')
                    ? \App\Models\Admin::class : \App\Models\User::class;
                    $user = $model::where('email', $this->email)->first();

                    if($user && Hash::check($value, $user->password)){
                        Auth::guard($guard)->login($user, $this->filled('remember'));
                    }else{
                        $fail('ログイン情報が登録されていません');
                    }
                },
            ]
        ];
    }

    public function messages()
    {
        return [
            'email.required'    => 'メールアドレスを入力してください',
            'email.string'      => 'メールアドレスは文字列で入力してください',
            'email.email'       => 'メールアドレスの形式で入力してください',
            'email.max'         => 'メールアドレスは255文字以内で入力してください',
            'password.required' => 'パスワードを入力してください',
            'password.string'   => 'パスワードは文字列で入力してください',
            'password.min'      => 'パスワードは8文字以上で入力してください',
            'password.max'      => 'パスワードは255文字以内で入力してください',
        ];
    }
}