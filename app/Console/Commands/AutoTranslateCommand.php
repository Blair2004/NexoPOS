<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Command that auto-translates the language JSON files found in /lang using
 * an external translation HTTP service.
 *
 * Source language is always English (en.json). Each destination file name
 * (e.g. fr.json) determines the destination language code ("fr").
 *
 * It will POST to TRANSLATOR_ENDPOINT + '/translate' with JSON body:
 *  {
 *      sourceLanguage: 'en',
 *      destinationLanguage: 'fr',
 *      content: 'Original text'
 *  }
 * Response is expected to be JSON containing:
 *  {
 *      translated: 'Texte traduit',
 *      sourceLanguage: 'en',
 *      destinationLanguage: 'fr',
 *      destinationLanguageName: 'French'
 *  }
 */
class AutoTranslateCommand extends Command
{
    protected $signature = 'ns:translations:auto {--file=} {--dry} {--force : Re-translate even if a value already exists}';
    protected $description = 'Auto translate missing (or all with --force) keys using external HTTP translation service.';

    public function handle(): int
    {
        $endpoint = rtrim(env('TRANSLATOR_ENDPOINT', ''), '/');
        if (empty($endpoint)) {
            $this->error('TRANSLATOR_ENDPOINT not configured in .env');
            return self::FAILURE;
        }

        $langPath = base_path('lang');
        $enFile = $langPath . DIRECTORY_SEPARATOR . 'en.json';
        if (!is_file($enFile)) {
            $this->error('Missing source en.json file.');
            return self::FAILURE;
        }

        $en = json_decode(file_get_contents($enFile), true);
        if (!is_array($en)) {
            $this->error('Invalid en.json format.');
            return self::FAILURE;
        }

        $targetFiles = [];
        if ($this->option('file')) {
            // the option file might consist of multiple files separated by commas
            $files = explode(',', $this->option('file'));

            foreach ($files as $file) {
                $file = trim($file);
                if (empty($file)) { continue; }
                $single = $langPath . DIRECTORY_SEPARATOR . $file;
                if (!is_file($single)) {
                    $this->error('Specified file not found: ' . $single);
                    return self::FAILURE;
                }
                $targetFiles[] = $single;
            }

            if (empty($targetFiles)) {
                $this->error('No valid files specified.');
                return self::FAILURE;
            }

        } else {
            foreach (glob($langPath . DIRECTORY_SEPARATOR . '*.json') as $file) {
                if (basename($file) === 'en.json') { continue; }
                $targetFiles[] = $file;
            }
        }

        if (empty($targetFiles)) {
            $this->warn('No destination language files found.');
            return self::SUCCESS;
        }

        $force = (bool)$this->option('force');
        $dry = (bool)$this->option('dry');

        foreach ($targetFiles as $file) {
            $basename = basename($file);
            $langCode = Str::before($basename, '.json');
            if ($langCode === 'en') { continue; }

            $this->newLine();
            $this->info("Processing {$basename} (lang: {$langCode})");

            $data = [];
            if (is_file($file)) {
                $decoded = json_decode(file_get_contents($file), true);
                if (is_array($decoded)) { $data = $decoded; }
            }

            $keys = array_keys($en);
            $total = count($keys);
            $translatedCount = 0; $skipped = 0; $errors = 0;

            $this->output->progressStart($total);
            foreach ($keys as $key) {
                $this->output->progressAdvance();
                $currentValue = $data[$key] ?? null;
                $needsTranslation = $force || !array_key_exists($key, $data) || $data[$key] === '' || $data[$key] === $en[$key];
                if (!$needsTranslation) { $skipped++; continue; }

                $payload = [
                    'sourceLanguage' => 'en',
                    'destinationLanguage' => $langCode,
                    'content' => $key,
                ];
                try {
                    $response = Http::timeout(30)->post($endpoint . '/translate', $payload);
                    if ($response->failed()) { 
                        $errors++; continue; 
                    }
                    $json = $response->json();
                    if (!is_array($json) || !array_key_exists('translated', $json)) { $errors++; continue; }
                    $data[$key] = $json['translated'];
                    $translatedCount++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("Error translating key '{$key}': " . $e->getMessage());
                }
            }
            $this->output->progressFinish();

            ksort($data); // stable order by key alpha
            if (!$dry) {
                file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            $this->line("Translated: {$translatedCount}, Skipped: {$skipped}, Errors: {$errors}");
            if ($dry) { $this->comment('Dry run: no file written.'); }
        }

        $this->info('Auto translation complete.');
        return self::SUCCESS;
    }
}
