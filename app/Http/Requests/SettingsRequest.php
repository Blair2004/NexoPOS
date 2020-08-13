<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Event;
use App\Services\Helper;
use TorMorten\Eventy\Facades\Events as Hook;
use App\Crud\Applications;
use App\Exceptions\CoreException;
use App\Services\CrudService;
use App\Services\SettingsPage;
use Exception;
use Illuminate\Support\Arr;

class SettingsRequest extends BaseCrudRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // we'll check here the user has enough permissions
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $service        =   Hook::filter( 'ns.settings', false, $this->route( 'identifier' ) );

        if ( $service instanceof SettingsPage ) {
            return $service->validateForm( $this );
        }

        throw new Exception( 
            sprintf( __( 'Unable to initialize the settings page. The identifier "%s" cannot be instantiated.' ), 
            $this->route( 'identifier' ) 
        ) );
    }
}
