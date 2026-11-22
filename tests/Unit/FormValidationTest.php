<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class FormValidationTest extends TestCase
{
    public function test_nullable_skips_empty_values_but_preserves_required_and_present_value_checks(): void
    {
        $cases = [];

        foreach ( [null, '', ['undefined' => true]] as $value ) {
            $cases[] = ['value' => $value, 'validation' => 'numeric|gte:1|nullable', 'expected' => []];
            $cases[] = ['value' => $value, 'validation' => 'nullable|required|numeric', 'expected' => ['required']];
        }

        $cases[] = ['value' => 'bad', 'validation' => 'nullable|numeric', 'expected' => ['numeric']];
        $cases[] = ['value' => 0, 'validation' => 'nullable|numeric|gt:0', 'expected' => ['gt']];
        $cases[] = ['value' => false, 'validation' => 'nullable|numeric', 'expected' => ['numeric']];

        $this->assertValidationCases( $cases );
    }

    public function test_numeric_accepts_finite_decimal_values_and_rejects_coerced_non_numbers(): void
    {
        $cases = [];

        foreach ( [0, -12, 1.25, '0', '-12.5', '+.5', '12.', '1e3', ' 2.5 '] as $value ) {
            $cases[] = ['value' => $value, 'validation' => 'numeric', 'expected' => []];
        }

        foreach ( [null, '', ' ', false, true, [], [12], '12abc', '0x10', 'Infinity', 'NaN', '1e999'] as $value ) {
            $cases[] = ['value' => $value, 'validation' => 'numeric', 'expected' => ['numeric']];
        }

        $cases[] = ['value' => 0, 'validation' => 'sometimes|numeric|gte:1', 'expected' => ['gte']];
        $cases[] = ['value' => '-1.5', 'validation' => 'number', 'expected' => ['number']];
        $cases[] = ['value' => '123', 'validation' => 'number', 'expected' => []];

        $this->assertValidationCases( $cases );
    }

    public function test_comparisons_handle_boundaries_numeric_strings_and_invalid_operands(): void
    {
        $cases = [];

        foreach ( ['gte', 'lte', 'gt', 'lt'] as $rule ) {
            foreach ( [-1.5, 0, 2.5] as $value ) {
                $passes = match ( $rule ) {
                    'gte' => $value >= 0,
                    'lte' => $value <= 0,
                    'gt' => $value > 0,
                    'lt' => $value < 0,
                };

                foreach ( [$value, (string) $value] as $input ) {
                    $cases[] = ['value' => $input, 'validation' => "{$rule}:0", 'expected' => $passes ? [] : [$rule]];
                }
            }

            foreach ( [null, '', false, [], 'bad'] as $value ) {
                $cases[] = ['value' => $value, 'validation' => "{$rule}:0", 'expected' => [$rule]];
            }

            $cases[] = ['value' => 5, 'validation' => "{$rule}:missing", 'expected' => [$rule]];
            $cases[] = ['value' => 5, 'validation' => "{$rule}:limit", 'form' => ['limit' => null], 'expected' => [$rule]];
        }

        $cases[] = ['value' => '-1.25', 'validation' => ['numeric', 'gte:-1.5', 'lte:0', 'gt:-2', 'lt:1e1'], 'expected' => []];
        $cases[] = ['value' => '2', 'validation' => 'lt:10', 'expected' => []];
        $cases[] = ['value' => '10', 'validation' => 'gt:2', 'expected' => []];

        $this->assertValidationCases( $cases );
    }

    public function test_comparisons_resolve_sibling_and_nested_field_values(): void
    {
        $cases = [];

        foreach ( ['gte', 'lte', 'gt', 'lt'] as $rule ) {
            $passesEquality = in_array( $rule, ['gte', 'lte'], true );
            $validValue = in_array( $rule, ['gte', 'gt'], true ) ? '10.5' : '-1.5';
            $invalidValue = in_array( $rule, ['gte', 'gt'], true ) ? '-1.5' : '10.5';

            foreach ( ['limit' => ['limit' => '2'], 'settings.limit' => ['settings' => ['limit' => '2']]] as $path => $form ) {
                $cases[] = ['value' => '2', 'validation' => "{$rule}:{$path}", 'form' => $form, 'expected' => $passesEquality ? [] : [$rule]];
                $cases[] = ['value' => $validValue, 'validation' => "{$rule}:{$path}", 'form' => $form, 'expected' => []];
                $cases[] = ['value' => $invalidValue, 'validation' => "{$rule}:{$path}", 'form' => $form, 'expected' => [$rule]];
            }
        }

        $this->assertValidationCases( $cases );
    }

    /**
     * @param array<int, array{value: mixed, validation: string|array<int, string>, expected: array<int, string>, form?: array<string, mixed>}> $cases
     */
    private function assertValidationCases( array $cases ): void
    {
        $script = <<<'JS'
import FormValidation from './resources/ts/libraries/form-validation.ts';
let input = '';
for await (const chunk of process.stdin) {
    input += chunk;
}
const validator = new FormValidation();
const results = JSON.parse(input).map(testCase => {
    const field = {
        name: 'amount',
        value: testCase.value?.undefined ? undefined : testCase.value,
        validation: testCase.validation,
        errors: [{ identifier: 'stale' }]
    };
    validator.checkField(field, testCase.form ?? {});
    return { errors: field.errors.map(error => error.identifier), touched: field.touched };
});
process.stdout.write(JSON.stringify(results));
JS;

        $process = new Process( ['node', '--experimental-strip-types', '--input-type=module', '-e', $script], dirname( __DIR__, 2 ) );
        $process->setInput( json_encode( $cases, JSON_THROW_ON_ERROR ) );
        $process->mustRun();
        $results = json_decode( $process->getOutput(), true, flags: JSON_THROW_ON_ERROR );

        foreach ( $cases as $index => $case ) {
            $this->assertSame( $case['expected'], $results[$index]['errors'], json_encode( $case, JSON_THROW_ON_ERROR ) );
            $this->assertTrue( $results[$index]['touched'] );
        }
    }
}
