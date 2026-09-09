<?php

namespace Modules\NsOxen\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\NsOxen\Models\Conversation;
use Modules\NsOxen\Models\Message;
use Throwable;

class ConversationAttachmentService
{
    public const MAX_FILES = 4;

    public const MAX_FILE_KILOBYTES = 2048;

    public const MAX_TOTAL_BYTES = 6 * 1024 * 1024;

    public const EXTENSIONS = [
        'pdf', 'csv', 'tsv', 'xls', 'xlsx', 'txt', 'md', 'json', 'html', 'xml',
        'doc', 'docx', 'rtf', 'odt', 'ppt', 'pptx',
    ];

    private const MIME_TYPES = [
        'pdf' => 'application/pdf',
        'csv' => 'text/csv',
        'tsv' => 'text/tsv',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain',
        'md' => 'text/markdown',
        'json' => 'application/json',
        'html' => 'text/html',
        'xml' => 'application/xml',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'rtf' => 'application/rtf',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    private const ACCEPTED_MIME_TYPES = [
        'pdf' => ['application/pdf'],
        'csv' => ['text/csv', 'application/csv', 'text/plain'],
        'tsv' => ['text/tsv', 'text/tab-separated-values', 'text/plain'],
        'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'txt' => ['text/plain'],
        'md' => ['text/markdown', 'text/plain'],
        'json' => ['application/json', 'text/json', 'text/plain'],
        'html' => ['text/html', 'text/plain'],
        'xml' => ['application/xml', 'text/xml', 'text/plain'],
        'doc' => ['application/msword', 'application/x-ole-storage', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'rtf' => ['application/rtf', 'text/rtf', 'text/plain'],
        'odt' => ['application/vnd.oasis.opendocument.text', 'application/zip'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/x-ole-storage', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    ];

    /**
     * @param list<UploadedFile> $files
     * @return list<array<string, int|string|null>>
     */
    public function store( Conversation $conversation, Message $message, array $files ): array
    {
        if ( $files === [] ) {
            return [];
        }
        if ( count( $files ) > self::MAX_FILES || collect( $files )->sum( fn ( UploadedFile $file ): int => (int) $file->getSize() ) > self::MAX_TOTAL_BYTES ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'Up to four attachments totaling 6 MB are allowed.', 'NsOxen' ) );
        }

        $directory = $this->messageDirectory( $conversation, $message );
        $stored = [];

        try {
            foreach ( $files as $file ) {
                $extension = Str::lower( $file->getClientOriginalExtension() );
                if ( ! $this->supports( $file ) || (int) $file->getSize() > self::MAX_FILE_KILOBYTES * 1024 ) {
                    throw new OxenException( 'VALIDATION_FAILED', __m( 'An attachment has an unsupported type or size.', 'NsOxen' ) );
                }
                $identifier = (string) Str::uuid();
                $path = $file->storeAs( $directory, $identifier . '.' . $extension, 'local' );
                if ( ! is_string( $path ) ) {
                    throw new OxenException( 'ATTACHMENT_UPLOAD_FAILED', __m( 'An attachment could not be stored.', 'NsOxen' ), 500 );
                }

                $stored[] = [
                    'id' => $identifier,
                    'name' => $this->safeName( $file->getClientOriginalName() ),
                    'mime_type' => self::MIME_TYPES[$extension],
                    'size' => (int) $file->getSize(),
                    'path' => $path,
                    'sha256' => hash_file( 'sha256', Storage::disk( 'local' )->path( $path ) ),
                    'user_id' => (int) $conversation->user_id,
                    'store_id' => $this->storeId(),
                ];
            }
        } catch ( Throwable $exception ) {
            Storage::disk( 'local' )->deleteDirectory( $directory );
            if ( $exception instanceof OxenException ) {
                throw $exception;
            }

            report( $exception );
            throw new OxenException( 'ATTACHMENT_UPLOAD_FAILED', __m( 'An attachment could not be stored.', 'NsOxen' ), 500 );
        }

        return $stored;
    }

    /** @return list<array{id: string, name: string, mime_type: string, size: int}> */
    public function present( array $attachments ): array
    {
        return collect( $attachments )
            ->filter( fn ( mixed $attachment ): bool => is_array( $attachment ) )
            ->map( fn ( array $attachment ): array => [
                'id' => (string) ( $attachment['id'] ?? '' ),
                'name' => (string) ( $attachment['name'] ?? __m( 'Attachment', 'NsOxen' ) ),
                'mime_type' => (string) ( $attachment['mime_type'] ?? 'application/octet-stream' ),
                'size' => (int) ( $attachment['size'] ?? 0 ),
            ] )
            ->values()
            ->all();
    }

    /** @return string|list<array<string, string>> */
    public function content( Message $message, string $text ): string|array
    {
        $attachments = data_get( $message->metadata, 'attachments', [] );
        if ( ! is_array( $attachments ) || $attachments === [] ) {
            return $text;
        }

        $content = [[
            'type' => 'input_text',
            'text' => $text !== '' ? $text : __m( 'Review the attached file(s).', 'NsOxen' ),
        ]];

        foreach ( $attachments as $attachment ) {
            if ( ! is_array( $attachment ) || ! $this->isReadableAttachment( $attachment, $message ) ) {
                $content[] = [
                    'type' => 'input_text',
                    'text' => sprintf( __m( 'Attachment "%s" is no longer available.', 'NsOxen' ), (string) ( $attachment['name'] ?? __m( 'unknown', 'NsOxen' ) ) ),
                ];

                continue;
            }

            $bytes = Storage::disk( 'local' )->get( $attachment['path'] );
            if ( ! is_string( $attachment['sha256'] ?? null ) || ! hash_equals( $attachment['sha256'], hash( 'sha256', $bytes ) ) ) {
                $content[] = [
                    'type' => 'input_text',
                    'text' => sprintf( __m( 'Attachment "%s" failed its integrity check.', 'NsOxen' ), (string) $attachment['name'] ),
                ];

                continue;
            }
            $content[] = [
                'type' => 'input_file',
                'filename' => (string) $attachment['name'],
                'file_data' => 'data:' . $attachment['mime_type'] . ';base64,' . base64_encode( $bytes ),
            ];
        }

        return $content;
    }

    public function estimatedCharacterCost( Message $message ): int
    {
        $attachments = data_get( $message->metadata, 'attachments', [] );

        return collect( is_array( $attachments ) ? $attachments : [] )
            ->sum( fn ( mixed $attachment ): int => is_array( $attachment ) ? max( 0, (int) ( $attachment['size'] ?? 0 ) ) * 2 : 0 );
    }

    public function deleteConversation( Conversation|string $conversation ): void
    {
        $publicId = $conversation instanceof Conversation ? $conversation->public_id : $conversation;
        if ( Str::isUuid( $publicId ) ) {
            Storage::disk( 'local' )->deleteDirectory( 'nsoxen/attachments/' . $publicId );
        }
    }

    public function supports( UploadedFile $file ): bool
    {
        $extension = Str::lower( $file->getClientOriginalExtension() );
        $mimeType = $file->getMimeType();

        return $file->isValid()
            && isset( self::MIME_TYPES[$extension], self::ACCEPTED_MIME_TYPES[$extension] )
            && is_string( $mimeType )
            && in_array( $mimeType, self::ACCEPTED_MIME_TYPES[$extension], true );
    }

    private function isReadableAttachment( array $attachment, Message $message ): bool
    {
        $path = $attachment['path'] ?? null;
        $conversation = $message->conversation;
        $expectedPrefix = $conversation instanceof Conversation
            ? 'nsoxen/attachments/' . $conversation->public_id . '/' . $message->id . '/'
            : null;

        return is_string( $path )
            && is_string( $expectedPrefix )
            && Str::startsWith( $path, $expectedPrefix )
            && (int) ( $attachment['user_id'] ?? 0 ) === (int) $conversation->user_id
            && ( $attachment['store_id'] ?? null ) === $this->storeId()
            && Storage::disk( 'local' )->exists( $path );
    }

    private function messageDirectory( Conversation $conversation, Message $message ): string
    {
        return 'nsoxen/attachments/' . $conversation->public_id . '/' . $message->id;
    }

    private function safeName( string $name ): string
    {
        $name = basename( str_replace( "\\", '/', $name ) );
        $name = preg_replace( '/[\x00-\x1F\x7F]/u', '', $name ) ?: __m( 'attachment', 'NsOxen' );

        return Str::limit( $name, 255, '' );
    }

    private function storeId(): ?int
    {
        $store = isset( ns()->store ) && method_exists( ns()->store, 'getCurrentStore' ) ? ns()->store->getCurrentStore() : null;

        return $store?->id;
    }
}
