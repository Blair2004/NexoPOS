<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AutoTranslateCommandTest extends TestCase
{
    private string $targetFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->targetFile = lang_path( 'zz-auto-translate-test.json' );
        config()->set( 'services.translator.endpoint', 'https://translator.test' );
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        if ( is_file( $this->targetFile ) ) {
            unlink( $this->targetFile );
        }

        parent::tearDown();
    }

    public function test_it_displays_http_failures_and_returns_a_failure_exit_code(): void
    {
        [$sourceKey] = $this->createTargetFileWithOneMissingTranslation();

        Http::fake( [
            'https://translator.test/translate' => Http::response( [
                'message' => 'Service unavailable',
            ], 503 ),
        ] );

        $this->artisan( 'ns:translations:auto', [
            '--file' => basename( $this->targetFile ),
            '--dry' => true,
        ] )
            ->expectsOutputToContain( "Translator returned HTTP 503 while translating '{$sourceKey}'." )
            ->expectsOutputToContain( 'Auto translation completed with errors.' )
            ->assertFailed();
    }

    public function test_it_uses_the_configured_endpoint_and_returns_success(): void
    {
        [$sourceKey, $sourceText] = $this->createTargetFileWithOneMissingTranslation();

        Http::fake( [
            'https://translator.test/translate' => Http::response( [
                'translated' => 'Texte traduit',
            ] ),
        ] );

        $this->artisan( 'ns:translations:auto', [
            '--file' => basename( $this->targetFile ),
            '--dry' => true,
        ] )
            ->expectsOutputToContain( 'Auto translation complete.' )
            ->assertSuccessful();

        Http::assertSentCount( 1 );
        Http::assertSent( function ( Request $request ) use ( $sourceKey, $sourceText ): bool {
            return $request->url() === 'https://translator.test/translate'
                && $request['content'] === $sourceKey
                && $sourceText === $sourceKey
                && $request['destinationLanguage'] === 'zz-auto-translate-test';
        } );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function createTargetFileWithOneMissingTranslation(): array
    {
        $sourceTranslations = json_decode( file_get_contents( lang_path( 'en.json' ) ), true, 512, JSON_THROW_ON_ERROR );
        $sourceKey = array_key_first( $sourceTranslations );
        $sourceText = $sourceTranslations[$sourceKey];
        $targetTranslations = [];

        foreach ( $sourceTranslations as $key => $value ) {
            $targetTranslations[$key] = '__translated__' . $value;
        }

        $targetTranslations[$sourceKey] = $sourceText;
        file_put_contents(
            $this->targetFile,
            json_encode( $targetTranslations, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE ),
        );

        return [$sourceKey, $sourceText];
    }
}
