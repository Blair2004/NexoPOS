<?php

namespace App\Http\Requests;

use App\Accounting\AccountingRuleValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class SaveTransactionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rule = $this->input( 'rule', [] );

        if ( ! isset( $rule['lines'] ) && isset( $rule['account_id'], $rule['action'], $rule['offset_account_id'], $rule['do'] ) ) {
            $rule['lines'] = [
                [
                    'account_id' => $rule['account_id'],
                    'effect' => $rule['action'],
                    'amount_source' => 'total',
                    'display_order' => 0,
                ],
                [
                    'account_id' => $rule['offset_account_id'],
                    'effect' => $rule['do'],
                    'amount_source' => 'total',
                    'display_order' => 1,
                ],
            ];
        }

        $this->merge( [ 'rule' => $rule ] );
    }

    public function rules(): array
    {
        return [
            'rule.id' => [ 'nullable', 'integer', 'exists:nexopos_transactions_actions_rules,id' ],
            'rule.on' => [ 'required', 'string', 'max:255' ],
            'rule.active' => [ 'sometimes', 'boolean' ],
            'rule.lines' => [ 'required', 'array', 'min:2' ],
            'rule.lines.*.id' => [ 'nullable', 'integer' ],
            'rule.lines.*.account_id' => [ 'nullable', 'integer' ],
            'rule.lines.*.dynamic_account_role' => [ 'nullable', 'string', 'max:255' ],
            'rule.lines.*.effect' => [ 'required', Rule::in( [ 'increase', 'decrease' ] ) ],
            'rule.lines.*.amount_source' => [ 'required', 'string', 'max:255' ],
            'rule.lines.*.display_order' => [ 'nullable', 'integer', 'min:0' ],
        ];
    }

    public function after(): array
    {
        return [
            function ( Validator $validator ): void {
                if ( $validator->errors()->isNotEmpty() ) {
                    return;
                }

                try {
                    app( AccountingRuleValidator::class )->validate( $this->validated( 'rule' ) );
                } catch ( ValidationException $exception ) {
                    foreach ( $exception->errors() as $field => $messages ) {
                        foreach ( $messages as $message ) {
                            $validator->errors()->add( $field, $message );
                        }
                    }
                }
            },
        ];
    }
}
