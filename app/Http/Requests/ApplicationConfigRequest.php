<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationConfigRequest extends FormRequest
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
    public function rules()
    {
        return [
            'ns_store_name' => 'required',
            'admin_email' => 'email|required',
            'admin_username' => 'min:5|required',
            'password' => 'min:6|required',
            'confirm_password' => 'same:password',
        ];
    }
}
