<?php

namespace App\Console\Commands;

use App\Services\ThemeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ThemeSymlinkCommand extends Command
{
    protected $signature = 'themes:symlink {namespace?}';

    protected $description = 'Create symbolic links for theme assets';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        /**
         * @var ThemeService
         */
        $themeService = app()->make(ThemeService::class);

        if (!empty($this->argument('namespace'))) {
            $theme = $themeService->get($this->argument('namespace'));

            if ($theme) {
                $themeService->createSymLink($this->argument('namespace'));

                $this->newLine();

                return $this->info(sprintf('The symbolic link directory has been created/refreshed for the theme "%s".', $theme['name']));
            }

            return $this->error(sprintf('Unable to find the theme "%s".', $this->argument('namespace')));
        } else {
            $themes = $themeService->get();

            /**
             * let's clear all existing links
             */
            if (is_dir(public_path('themes'))) {
                Storage::disk('ns')->deleteDirectory('public/themes');
            }
            Storage::disk('ns')->makeDirectory('public/themes');

            $this->withProgressBar($themes, function ($theme) use ($themeService) {
                try {
                    $themeService->createSymLink($theme['namespace']);
                } catch (\Exception $e) {
                    // Continue even if one symlink fails
                }
            });

            $this->newLine();

            return $this->info(sprintf('The symbolic link directory has been created/refreshed for "%s" themes.', count($themes)));
        }
    }
}
