<?php

namespace App\Console\Commands;

use App\Services\ThemeService;
use Illuminate\Console\Command;

class ThemeListCommand extends Command
{
    protected $signature = 'themes:list';

    protected $description = 'List all available themes';

    public function handle()
    {
        /**
         * @var ThemeService
         */
        $themeService = app()->make(ThemeService::class);
        $themes = $themeService->get();

        if (empty($themes)) {
            return $this->info('No themes found.');
        }

        $rows = [];

        foreach ($themes as $theme) {
            $rows[] = [
                $theme['namespace'],
                $theme['name'],
                $theme['version'] ?? 'N/A',
                $theme['enabled'] ? 'Yes' : 'No',
                $theme['author'] ?? 'N/A',
            ];
        }

        $this->table(
            ['Namespace', 'Name', 'Version', 'Enabled', 'Author'],
            $rows
        );

        $this->newLine();
        $this->info(sprintf('Total themes: %d', count($themes)));
        $this->info(sprintf('Enabled: %d', count($themeService->getEnabled() ? [$themeService->getEnabled()] : [])));
        $this->info(sprintf('Disabled: %d', count($themeService->getDisabled())));
    }
}
