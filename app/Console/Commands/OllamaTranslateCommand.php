<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OllamaTranslateCommand extends Command
{
    protected $signature = 'ns:translations:ollama
        {--base=en : Base language file code}
        {--lang=* : Target language code. Can be passed more than once}
        {--file=* : Target JSON file name. Can be passed more than once}
        {--dry : Show what would be translated without writing files}
        {--force : Re-translate values even when they already exist}
        {--limit= : Maximum number of lines to translate per language}';

    protected $description = 'Translate lang JSON files line by line using Ollama.';

    public function handle(): int
    {
        $endpoint = rtrim( config( 'services.ollama.endpoint', env( 'OLLAMA_ENDPOINT', 'http://127.0.0.1:11434' ) ), '/' );
        $model = config( 'services.ollama.model', env( 'OLLAMA_MODEL' ) );
        $timeout = (int) config( 'services.ollama.timeout', env( 'OLLAMA_TIMEOUT', 120 ) );

        if ( empty( $model ) ) {
            $this->error( 'OLLAMA_MODEL is not configured in .env.' );

            return self::FAILURE;
        }

        $langPath = base_path( 'lang' );
        $baseLanguage = $this->option( 'base' ) ?: 'en';
        $baseFile = $langPath . DIRECTORY_SEPARATOR . $baseLanguage . '.json';

        if ( ! is_file( $baseFile ) && $baseLanguage !== 'all' ) {
            $this->error( "Missing base language file: {$baseLanguage}.json" );

            return self::FAILURE;
        }

        if ( $baseLanguage === 'all' ) {
            $lang = config( 'nexopos.languages', [] );

            foreach( $lang as $code => $name ) {
                
            }
        }

        $sourceTranslations = $this->readJsonFile( $baseFile );
        if ( $sourceTranslations === null ) {
            return self::FAILURE;
        }

        $targetFiles = $this->resolveTargetFiles( $langPath, $baseLanguage );
        if ( $targetFiles === null ) {
            return self::FAILURE;
        }

        if ( empty( $targetFiles ) ) {
            $this->warn( 'No destination language files found.' );

            return self::SUCCESS;
        }

        foreach ( $targetFiles as $file ) {
            if ( ! $this->translateFile( $file, $sourceTranslations, $baseLanguage, $endpoint, $model, $timeout ) ) {
                return self::FAILURE;
            }
        }

        $this->info( 'Ollama translation complete.' );

        return self::SUCCESS;
    }

    private function translateFile( string $file, array $sourceTranslations, string $baseLanguage, string $endpoint, string $model, int $timeout ): bool
    {
        $basename = basename( $file );
        $targetLanguage = Str::beforeLast( $basename, '.json' );
        $targetLanguageName = config( "nexopos.languages.{$targetLanguage}", $targetLanguage );
        $baseLanguageName = config( "nexopos.languages.{$baseLanguage}", $baseLanguage );
        $force = (bool) $this->option( 'force' );
        $dry = (bool) $this->option( 'dry' );
        $limit = $this->option( 'limit' ) !== null ? (int) $this->option( 'limit' ) : null;
        $translated = 0;
        $skipped = 0;
        $errors = 0;

        $this->newLine();
        $this->info( "Processing {$basename} ({$targetLanguageName})" );

        $targetTranslations = is_file( $file ) ? $this->readJsonFile( $file ) : [];
        if ( $targetTranslations === null ) {
            return false;
        }

        $this->output->progressStart( count( $sourceTranslations ) );

        foreach ( $sourceTranslations as $key => $sourceText ) {
            $this->output->progressAdvance();

            if ( $limit !== null && $translated >= $limit ) {
                $skipped++;

                continue;
            }

            $sourceText = is_string( $sourceText ) ? $sourceText : $key;
            $currentValue = $targetTranslations[$key] ?? null;
            $needsTranslation = $force ||
                ! array_key_exists( $key, $targetTranslations ) ||
                $currentValue === '' ||
                $currentValue === null ||
                $currentValue === $sourceText;

            if ( ! $needsTranslation ) {
                $skipped++;

                continue;
            }

            if ( $dry ) {
                $translated++;

                continue;
            }

            $result = $this->translateLine( $sourceText, $baseLanguageName, $targetLanguageName, $endpoint, $model, $timeout );

            if ( $result === null ) {
                $errors++;

                continue;
            }

            $targetTranslations[$key] = $result;
            $translated++;
        }

        $this->output->progressFinish();

        if ( ! $dry ) {
            $orderedTranslations = [];
            foreach ( array_keys( $sourceTranslations ) as $key ) {
                if ( array_key_exists( $key, $targetTranslations ) ) {
                    $orderedTranslations[$key] = $targetTranslations[$key];
                }
            }

            file_put_contents( $file, json_encode( $orderedTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
        }

        $this->line( "Translated: {$translated}, Skipped: {$skipped}, Errors: {$errors}" );
        if ( $dry ) {
            $this->comment( 'Dry run: no file written.' );
        }

        return true;
    }

    private function translateLine( string $sourceText, string $baseLanguageName, string $targetLanguageName, string $endpoint, string $model, int $timeout ): ?string
    {
        $prompt = implode( "\n", [
            "Translate the following application localization line from {$baseLanguageName} to {$targetLanguageName}.",
            'Return only the translated text, with no quotes, markdown, notes, or alternatives.',
            'Preserve placeholders and formatting tokens exactly, including %s, %d, :name, {name}, HTML tags, escaped quotes, and punctuation spacing.',
            '',
            $sourceText,
        ] );

        try {
            $response = Http::timeout( $timeout )->post( $endpoint . '/api/generate', [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0,
                ],
            ] );
        } catch ( \Throwable $e ) {
            $this->error( "Ollama request failed: {$e->getMessage()}" );

            return null;
        }

        if ( $response->failed() ) {
            $this->error( "Ollama returned HTTP {$response->status()}." );

            return null;
        }

        $json = $response->json();
        if ( ! is_array( $json ) || ! isset( $json['response'] ) || ! is_string( $json['response'] ) ) {
            $this->error( 'Ollama returned an invalid response.' );

            return null;
        }

        return $this->cleanTranslation( $json['response'] );
    }

    private function cleanTranslation( string $translation ): string
    {
        $translation = trim( $translation );
        $translation = preg_replace( '/^```(?:\w+)?\s*/', '', $translation );
        $translation = preg_replace( '/\s*```$/', '', $translation );

        return trim( $translation, " \t\n\r\0\x0B\"'" );
    }

    private function resolveTargetFiles( string $langPath, string $baseLanguage ): ?array
    {
        $files = [];

        foreach ( $this->option( 'file' ) as $file ) {
            $path = $langPath . DIRECTORY_SEPARATOR . basename( $file );
            if ( ! is_file( $path ) ) {
                $this->error( "Specified file not found: {$path}" );

                return null;
            }
            $files[] = $path;
        }

        foreach ( $this->option( 'lang' ) as $language ) {
            $path = $langPath . DIRECTORY_SEPARATOR . $language . '.json';
            if ( ! is_file( $path ) ) {
                $this->error( "Specified language file not found: {$path}" );

                return null;
            }
            $files[] = $path;
        }

        if ( empty( $files ) ) {
            $files = glob( $langPath . DIRECTORY_SEPARATOR . '*.json' ) ?: [];
        }

        return collect( $files )
            ->unique()
            ->reject( fn( string $file ) => basename( $file ) === $baseLanguage . '.json' )
            ->values()
            ->all();
    }

    private function readJsonFile( string $file ): ?array
    {
        try {
            $decoded = json_decode( file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );
        } catch ( \Throwable $e ) {
            $this->error( 'Invalid JSON in ' . basename( $file ) . ': ' . $e->getMessage() );

            return null;
        }

        if ( ! is_array( $decoded ) ) {
            $this->error( basename( $file ) . ' must contain a JSON object.' );

            return null;
        }

        return $decoded;
    }
}
