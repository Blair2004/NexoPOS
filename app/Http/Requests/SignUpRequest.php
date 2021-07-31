<?php

namespace App\Http\Requests;

use App\Exceptions\NotAllowedException;
use Illuminate\Foundation\Http\FormRequest;
use App\Services\Options;

class SignUpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $options    =   app()->make( Options::class );

        if ( $options->get( 'ns_registration_enabled' ) !== 'yes' ) {
            throw new NotAllowedException( __( 'Unable to register. The registration is closed.' ) );
        }

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
            'username'          =>  'required|min:6',
            'email'             =>  'email',
            'password'          =>  'required',
            'password_confirm'  => 'same:password'
        ];
    }
}
