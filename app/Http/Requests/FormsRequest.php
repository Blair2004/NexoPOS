<?php

namespace App\Http\Requests;

use App\Services\CrudService;
use App\Services\SettingsPage;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use TorMorten\Eventy\Facades\Events;

class FormsRequest extends FormRequest
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
        $service = new CrudService;
        $instance = Events::filter( 'ns.forms', [], $this->route( 'resource' ) );

        if ( ! $instance instanceof SettingsPage ) {
            throw new Exception( sprintf(
                '%s is not an instanceof "%s".',
                $this->route( 'resource' ),
                SettingsPage::class
            ) );
        }

        return $instance->validateForm( $this );
    }
}
