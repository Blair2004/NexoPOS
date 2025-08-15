<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncTranslations extends Command
{
    protected $signature = 'ns:translations:sync {--dry : Only report missing keys, don\'t write files}';
    protected $description = 'Sync translation files with English source keys and pretty-print them.';

    public function handle(): int
    {
        $langPath = base_path('lang');
        $enFile = $langPath . DIRECTORY_SEPARATOR . 'en.json';
        if ( ! is_file($enFile) ) {
            $this->error('English source file en.json not found.');
            return self::FAILURE;
        }

        $enContent = file_get_contents($enFile);
        try {
            $en = json_decode($enContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->error('Invalid JSON in en.json : ' . $e->getMessage());
            return self::FAILURE;
        }
        if (!is_array($en)) {
            $this->error('English JSON is not an object.');
            return self::FAILURE;
        }

        $enKeys = array_keys($en);

        $files = glob($langPath . DIRECTORY_SEPARATOR . '*.json');
        $dry = $this->option('dry');

        $summary = [];
        foreach ( $files as $file ) {
            $basename = basename($file);
            if ($basename === 'en.json') {
                continue; // skip source
            }

            $raw = file_get_contents($file);
            $data = json_decode($raw, true);
            if ( ! is_array($data) ) {
                $this->warn("Skipping {$basename}: invalid JSON");
                continue;
            }

            $added = 0; $updatedEmpty = 0; $total = count($data);
            foreach ($enKeys as $key) {
                if ( ! array_key_exists($key, $data) ) {
                    $data[$key] = $en[$key];
                    $added++;
                } elseif ($data[$key] === '' || $data[$key] === null) {
                    $data[$key] = $en[$key];
                    $updatedEmpty++;
                }
            }

            // keep original ordering roughly by sorting keys as English ordering
            $ordered = [];
            foreach ($enKeys as $k) {
                if (array_key_exists($k, $data)) {
                    $ordered[$k] = $data[$k];
                }
            }

            if ( ! $dry ) {
                file_put_contents($file, json_encode($ordered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            $summary[$basename] = [
                'existing' => $total,
                'added' => $added,
                'filledEmpty' => $updatedEmpty,
                'final' => count($ordered),
            ];
        }

        if (!empty($summary)) {
            $this->table(['File','Existing','Added','Filled Empty','Final'], array_map(function($file,$info){
                return [ $file, $info['existing'], $info['added'], $info['filledEmpty'], $info['final'] ];
            }, array_keys($summary), $summary));
        }

        if ($dry) {
            $this->info('Dry run complete. No files were modified.');
        } else {
            $this->info('Translation files synced and pretty-printed.');
        }
        return self::SUCCESS;
    }
}
