<?php

namespace App\Http\Requests;

use App\Services\WidgetService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateWidgetLayoutRequest extends FormRequest
{
    private Collection $availableWidgets;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules( WidgetService $widgetService ): array
    {
        $this->availableWidgets = $widgetService->getWidgets()->keyBy( 'component-name' );

        return [
            'widgets' => [ 'present', 'array', 'list', 'max:100' ],
            'widgets.*' => [ 'required', 'array:identifier,layout' ],
            'widgets.*.identifier' => [
                'required',
                'string',
                'distinct:strict',
                Rule::in( $this->availableWidgets->keys()->all() ),
            ],
            'widgets.*.layout' => [ 'sometimes', 'nullable', 'string', 'regex:/^([1-3])x([1-5])$/' ],
        ];
    }

    /**
     * Validate each user selection against its server-defined widget policy.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ( Validator $validator ): void {
                foreach ( $this->input( 'widgets', [] ) as $index => $widgetConfig ) {
                    if ( ! is_array( $widgetConfig ) || empty( $widgetConfig['layout'] ) || empty( $widgetConfig['identifier'] ) ) {
                        continue;
                    }

                    $widget = $this->availableWidgets->get( $widgetConfig['identifier'] );

                    if ( $widget !== null && ! $widget->instance->supportsLayout( $widgetConfig['layout'] ) ) {
                        $validator->errors()->add(
                            "widgets.{$index}.layout",
                            __( 'The selected layout is not supported by this widget.' )
                        );
                    }
                }
            },
        ];
    }
}
