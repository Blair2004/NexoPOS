<?php

namespace App\Console\Commands;

use App\Services\ThemeService;
use Illuminate\Console\Command;

class ThemeEnableCommand extends Command
{
    protected $signature = 'themes:enable {namespace}';

    protected $description = 'Enable a theme';

    public function handle()
    {
        /**
         * @var ThemeService
         */
        $themeService = app()->make(ThemeService::class);
        $namespace = $this->argument('namespace');

        $theme = $themeService->get($namespace);

        if (!$theme) {
            return $this->error(sprintf('Unable to find the theme "%s".', $namespace));
        }

        if ($theme['enabled']) {
            return $this->info(sprintf('The theme "%s" is already enabled.', $theme['name']));
        }

        $result = $themeService->enable($namespace);

        if ($result['status'] === 'success') {
            return $this->info($result['message']);
        } else {
            return $this->error($result['message']);
        }
    }
}
