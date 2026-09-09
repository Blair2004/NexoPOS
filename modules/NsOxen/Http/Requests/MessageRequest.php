<?php

namespace Modules\NsOxen\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\NsOxen\Services\ConversationAttachmentService;

class MessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ns()->allowedTo( 'ns.oxen.use' );
    }

    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:12000', 'required_without:attachments'],
            'attachments' => ['nullable', 'array', 'max:' . ConversationAttachmentService::MAX_FILES],
            'attachments.*' => [
                'required',
                'file',
                'max:' . ConversationAttachmentService::MAX_FILE_KILOBYTES,
                function ( string $attribute, mixed $value, \Closure $fail ): void {
                    if ( ! $value instanceof \Illuminate\Http\UploadedFile || ! app( ConversationAttachmentService::class )->supports( $value ) ) {
                        $fail( __m( 'This attachment type is not supported.', 'NsOxen' ) );
                    }
                },
            ],
            'route_name' => ['nullable', 'string', 'max:160'],
            'entity_id' => ['nullable', 'integer'],
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [function ( Validator $validator ): void {
            $totalBytes = collect( $this->file( 'attachments', [] ) )
                ->sum( fn ( mixed $file ): int => method_exists( $file, 'getSize' ) ? (int) $file->getSize() : 0 );

            if ( $totalBytes > ConversationAttachmentService::MAX_TOTAL_BYTES ) {
                $validator->errors()->add( 'attachments', __m( 'Attachments cannot exceed 6 MB combined.', 'NsOxen' ) );
            }
        }];
    }
}
