<{{ '?php' }}
/**
 * Form Request
 * @module {{ $module[ 'namespace' ] }}
 * @since {{ $module[ 'version' ] }}
**/
namespace Modules\{{ $module[ 'namespace' ] }}\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {{ $name }} extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // ...
        ];
    }
}
