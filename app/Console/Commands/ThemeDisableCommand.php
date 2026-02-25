<?php

namespace App\Console\Commands;

use App\Services\ThemeService;
use Illuminate\Console\Command;

class ThemeDisableCommand extends Command
{
    protected $signature = 'themes:disable {namespace}';

    protected $description = 'Disable a theme';

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

        if (!$theme['enabled']) {
            return $this->info(sprintf('The theme "%s" is already disabled.', $theme['name']));
        }

        $result = $themeService->disable($namespace);

        if ($result['status'] === 'success') {
            return $this->info($result['message']);
        } else {
            return $this->error($result['message']);
        }
    }
}
