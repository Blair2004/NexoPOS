<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Event;
use App\Services\Helper;
use Hook;
use App\Crud\Applications;
use App\Exceptions\CoreException;
use App\Services\CrudService;
use App\Services\SettingsPage;
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
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $service        =   new SettingsPage;
        return $service->validateForm( $this );
    }
}
