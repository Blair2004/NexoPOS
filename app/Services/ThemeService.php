<?php

namespace App\Services;

use App\Classes\Hook;
use App\Classes\XMLParser;
use App\Events\ThemeDisabledEvent;
use App\Events\ThemeEnabledEvent;
use App\Exceptions\NotAllowedException;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleXMLElement;

class ThemeService
{
    private $themes = [];

    private Options $options;

    public function __construct()
    {
        if (Helper::installed()) {
            $this->options = app()->make(Options::class);
        }

        /**
         * creates the directory themes
         * if that doesn't exists
         */
        if (!is_dir(base_path('themes'))) {
            Storage::disk('ns')->makeDirectory('themes');
        }

        /**
         * create the public themes directory
         * if that doesn't exists
         */
        if (!is_dir(public_path('themes'))) {
            Storage::disk('ns-public')->makeDirectory('themes');
        }
    }

    /**
     * Load themes from themes directory.
     *
     * @param string|null $dir
     */
    public function load(?string $dir = null): void
    {
        /**
         * If we're not loading a specific theme directory
         */
        if ($dir == null) {
            $directories = Storage::disk('ns-themes')->directories();

            /**
             * Load each theme
             */
            collect($directories)->map(function ($theme) {
                return str_replace('/', '\\', $theme);
            })->each(function ($theme) {
                $this->__init($theme);
            });
        } else {
            $this->__init($dir);
        }
    }

    /**
     * Init a theme from a provided path.
     */
    public function __init(string $dir): void
    {
        /**
         * Loading config files from theme directory
         */
        $xmlConfigPath = base_path('themes') . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . 'config.xml';
        $xmlRelativePath = 'themes' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . 'config.xml';

        if (is_file($xmlConfigPath)) {
            $xmlContent = file_get_contents($xmlConfigPath);

            try {
                $parser = new XMLParser($xmlConfigPath);
                $config = (array) $parser->getXMLObject();
            } catch (Exception $exception) {
                throw new Exception(sprintf(
                    __('Failed to parse the configuration file on the following path "%s"'),
                    $xmlRelativePath
                ));
            }

            $xmlElement = new SimpleXMLElement($xmlContent);

            // Parse min-version and max-version from core element
            if ($xmlElement->core[0] instanceof SimpleXMLElement) {
                $attributes = $xmlElement->core[0]->attributes();
                $minVersion = 'min-version';
                $maxVersion = 'max-version';

                $config['core'] = [
                    'min-version' => ((string) $attributes->$minVersion) ?? null,
                    'max-version' => ((string) $attributes->$maxVersion) ?? null,
                ];
            }

            // Parse description with locale support
            $locales = $xmlElement->children()->description?->xpath('locale') ?? [];

            if (count($locales) > 0) {
                $config['description'] = collect($locales)->mapWithKeys(function ($locale) {
                    $locale = (array) $locale;

                    return [$locale['@attributes']['lang'] => $locale[0]];
                });
            } else {
                // Fallback: if there is a <description> element without <locale> children
                $descriptionNode = $xmlElement->children()->description ?? null;
                if ($descriptionNode instanceof SimpleXMLElement) {
                    $rawDescription = trim((string) $descriptionNode);
                    if ($rawDescription !== '') {
                        if (!isset($config['description']) || !is_array($config['description'])) {
                            $config['description'] = [];
                        }
                        if (!isset($config['description']['en'])) {
                            $config['description']['en'] = $rawDescription;
                        }
                    }
                }
            }

            // Parse features from config
            $features = $xmlElement->children()->features?->xpath('//item') ?? [];
            $config['features'] = collect($features)->mapWithKeys(function ($item) {
                $item = (array) $item;

                return [$item['@attributes']['identifier'] => [
                    'name' => $item['@attributes']['name'],
                    'identifier' => $item['@attributes']['identifier'],
                ]];
            })->toArray() ?? [];

            // If a theme has at least a namespace
            if ($config['namespace'] !== null) {
                // index path
                $themesPath = base_path('themes') . DIRECTORY_SEPARATOR;
                $currentThemePath = $themesPath . $dir . DIRECTORY_SEPARATOR;
                $indexPath = $currentThemePath . ucwords($config['namespace'] . 'Module.php');
                $previewImagePng = $currentThemePath . 'preview.png';
                $previewImageJpg = $currentThemePath . 'preview.jpg';

                // Check for preview image
                $config['preview-image'] = false;
                if (is_file($previewImagePng)) {
                    $config['preview-image'] = $previewImagePng;
                } elseif (is_file($previewImageJpg)) {
                    $config['preview-image'] = $previewImageJpg;
                }

                // check index existence
                $config['index-file'] = is_file($indexPath) ? $indexPath : false;
                $config['path'] = $currentThemePath;

                /**
                 * Check if the theme is currently enabled
                 */
                $enabledTheme = '';
                if (Helper::installed()) {
                    $enabledTheme = $this->options->get('enabled_theme', '');
                }

                $config['enabled'] = $config['namespace'] === $enabledTheme;

                $this->themes[$config['namespace']] = $config;
            }
        } else {
            Log::error(sprintf(__('No config.xml has been found on the directory : %s. This folder is ignored'), $dir));
        }
    }

    /**
     * Get theme(s).
     *
     * @param string|null $namespace
     * @return bool|array
     */
    public function get($namespace = null): bool|array
    {
        if ($namespace !== null) {
            return $this->themes[$namespace] ?? false;
        }

        return $this->themes;
    }

    /**
     * Get the currently enabled theme.
     *
     * @return array|bool
     */
    public function getEnabled()
    {
        $enabledTheme = array_filter($this->themes, function ($theme) {
            return $theme['enabled'] === true;
        });

        // Return first enabled theme (should only be one)
        return !empty($enabledTheme) ? reset($enabledTheme) : false;
    }

    /**
     * Get all disabled themes.
     *
     * @return array
     */
    public function getDisabled(): array
    {
        return array_filter($this->themes, function ($theme) {
            return $theme['enabled'] === false;
        });
    }

    /**
     * Get invalid themes.
     *
     * @return array
     */
    public function getInvalid(): array
    {
        // For now, themes without index-file or missing required config
        return array_filter($this->themes, function ($theme) {
            return !isset($theme['namespace']) || empty($theme['namespace']);
        });
    }

    /**
     * Enable a theme.
     * Only one theme can be enabled at a time.
     *
     * @param string $namespace
     * @return array
     */
    public function enable(string $namespace): array
    {
        if ($theme = $this->get($namespace)) {
            // Check version compatibility
            if (isset($theme['core'])) {
                $currentVersion = config('nexopos.version');

                if (!empty($theme['core']['min-version']) && version_compare($currentVersion, $theme['core']['min-version'], '<')) {
                    return [
                        'status' => 'error',
                        'code' => 'version_mismatch',
                        'message' => sprintf(
                            __('The theme "%s" requires NexoPOS version %s or higher. Current version: %s'),
                            $theme['name'],
                            $theme['core']['min-version'],
                            $currentVersion
                        ),
                    ];
                }

                if (!empty($theme['core']['max-version']) && version_compare($currentVersion, $theme['core']['max-version'], '>')) {
                    return [
                        'status' => 'error',
                        'code' => 'version_mismatch',
                        'message' => sprintf(
                            __('The theme "%s" is not compatible with NexoPOS version %s. Maximum supported version: %s'),
                            $theme['name'],
                            $currentVersion,
                            $theme['core']['max-version']
                        ),
                    ];
                }
            }

            // Disable currently enabled theme
            $currentEnabled = $this->getEnabled();
            if ($currentEnabled && $currentEnabled['namespace'] !== $namespace) {
                $this->disable($currentEnabled['namespace']);
            }

            // Enable the new theme
            $this->options->set('enabled_theme', $namespace);

            // Create symlink for theme assets
            $this->createSymLink($namespace);

            // Update theme status
            $this->themes[$namespace]['enabled'] = true;

            ThemeEnabledEvent::dispatch($theme);

            return [
                'status' => 'success',
                'code' => 'theme_enabled',
                'message' => sprintf(__('The theme "%s" has been enabled.'), $theme['name']),
                'theme' => $theme,
            ];
        }

        return [
            'status' => 'error',
            'code' => 'unknown_theme',
            'message' => sprintf(__('Unable to locate a theme having as identifier "%s".'), $namespace),
        ];
    }

    /**
     * Disable a theme.
     *
     * @param string $namespace
     * @return array
     */
    public function disable(string $namespace): array
    {
        if ($theme = $this->get($namespace)) {
            ThemeDisabledEvent::dispatch($theme);

            // Remove from enabled theme option
            $this->options->set('enabled_theme', '');

            // Remove symlink
            $this->removeSymLink($namespace);

            // Update theme status
            $this->themes[$namespace]['enabled'] = false;

            return [
                'status' => 'success',
                'code' => 'theme_disabled',
                'message' => sprintf(__('The theme "%s" has been disabled.'), $theme['name']),
                'theme' => $theme,
            ];
        }

        return [
            'status' => 'error',
            'code' => 'unknown_theme',
            'message' => __('Unable to disable the theme.'),
        ];
    }

    /**
     * Upload and extract theme zip file.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function upload(UploadedFile $file): array
    {
        $path = $file->store('', ['disk' => 'ns-themes-temp']);
        $fullPath = Storage::disk('ns-themes-temp')->path($path);
        $extractionFolderName = Str::uuid();
        $dir = dirname($fullPath);

        $archive = new \ZipArchive;
        $archive->open($fullPath);
        $archive->extractTo($dir . DIRECTORY_SEPARATOR . $extractionFolderName);
        $archive->close();

        /**
         * Unlink the uploaded zipfile
         */
        unlink($fullPath);

        $directory = Storage::disk('ns-themes-temp')->directories($extractionFolderName);

        if (count($directory) > 1) {
            throw new Exception(__('Unable to detect the folder from where to perform the installation.'));
        }

        $directoryName = pathinfo($directory[0])['basename'];
        $rawFiles = Storage::disk('ns-themes-temp')->allFiles($extractionFolderName);

        /**
         * Just retrieve the files name
         */
        $files = array_map(function ($file) {
            $info = pathinfo($file);

            return $info['basename'];
        }, $rawFiles);

        if (in_array('config.xml', $files)) {
            $file = $extractionFolderName . DIRECTORY_SEPARATOR . $directoryName . DIRECTORY_SEPARATOR . 'config.xml';
            $xml = new SimpleXMLElement(
                Storage::disk('ns-themes-temp')->get($file)
            );

            if (
                !isset($xml->namespace) ||
                !isset($xml->version) ||
                !isset($xml->name) ||
                $xml->getName() != 'theme'
            ) {
                /**
                 * the theme is invalid
                 */
                Storage::disk('ns-themes-temp')->deleteDirectory($extractionFolderName);

                return [
                    'status' => 'error',
                    'message' => __('Invalid theme config.xml file. The following elements are required: namespace, version, name, and root element must be "theme".'),
                ];
            }

            /**
             * Check if a similar theme already exists
             */
            if ($this->get((string) $xml->namespace)) {
                Storage::disk('ns-themes-temp')->deleteDirectory($extractionFolderName);

                return [
                    'status' => 'error',
                    'code' => 'theme_exists',
                    'message' => __('A theme with the same namespace already exists.'),
                ];
            }

            /**
             * Move theme to themes directory
             */
            $themePath = 'themes' . DIRECTORY_SEPARATOR . ucwords((string) $xml->namespace);
            $tempPath = $dir . DIRECTORY_SEPARATOR . $extractionFolderName . DIRECTORY_SEPARATOR . $directoryName;

            if (!is_dir(base_path($themePath))) {
                Storage::disk('ns')->makeDirectory($themePath);
            }

            /**
             * Copy files from temp to themes directory
             */
            $allFiles = Storage::disk('ns-themes-temp')->allFiles($extractionFolderName . DIRECTORY_SEPARATOR . $directoryName);

            foreach ($allFiles as $file) {
                $relativePath = str_replace($extractionFolderName . DIRECTORY_SEPARATOR . $directoryName . DIRECTORY_SEPARATOR, '', $file);
                $targetPath = $themePath . DIRECTORY_SEPARATOR . $relativePath;

                // Create directory if it doesn't exist
                $targetDir = dirname(base_path($targetPath));
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                copy(
                    Storage::disk('ns-themes-temp')->path($file),
                    base_path($targetPath)
                );
            }

            /**
             * Clean up temp directory
             */
            Storage::disk('ns-themes-temp')->deleteDirectory($extractionFolderName);

            /**
             * Load the new theme
             */
            $this->load(ucwords((string) $xml->namespace));

            return [
                'status' => 'success',
                'message' => sprintf(__('The theme "%s" has been successfully installed.'), (string) $xml->name),
                'theme' => $this->get((string) $xml->namespace),
            ];
        }

        /**
         * Clean up temp directory
         */
        Storage::disk('ns-themes-temp')->deleteDirectory($extractionFolderName);

        return [
            'status' => 'error',
            'message' => __('The uploaded file is not a valid theme. config.xml is missing.'),
        ];
    }

    /**
     * Delete a theme.
     *
     * @param string $namespace
     * @return array
     */
    public function delete(string $namespace): array
    {
        if ($theme = $this->get($namespace)) {
            if ($theme['enabled']) {
                return [
                    'status' => 'error',
                    'code' => 'theme_enabled',
                    'message' => sprintf(__('The theme "%s" is currently enabled and cannot be deleted. Please disable it first.'), $theme['name']),
                ];
            }

            /**
             * Remove symlink if exists
             */
            $this->removeSymLink($namespace);

            /**
             * Delete theme from filesystem
             */
            Storage::disk('ns-themes')->deleteDirectory(ucwords($namespace));

            /**
             * Remove from themes array
             */
            unset($this->themes[$namespace]);

            return [
                'status' => 'success',
                'code' => 'theme_deleted',
                'message' => sprintf(__('The theme "%s" has been successfully deleted.'), $theme['name']),
            ];
        }

        return [
            'status' => 'error',
            'code' => 'unknown_theme',
            'message' => sprintf(__('Unable to locate a theme having as identifier "%s".'), $namespace),
        ];
    }

    /**
     * Extract theme as zip file.
     *
     * @param string $namespace
     * @return array
     */
    public function extract(string $namespace): array
    {
        if ($theme = $this->get($namespace)) {
            $zipFile = storage_path() . DIRECTORY_SEPARATOR . 'theme.zip';

            // unlink old theme zip
            if (is_file($zipFile)) {
                unlink($zipFile);
            }

            $files = Storage::disk('ns-themes')->allFiles(ucwords($namespace));

            // create new archive
            $zipArchive = new \ZipArchive;
            $zipArchive->open(
                $zipFile,
                \ZipArchive::CREATE | \ZipArchive::OVERWRITE
            );
            $zipArchive->addEmptyDir(ucwords($namespace));

            foreach ($files as $file) {
                $zipArchive->addFile(base_path('themes') . DIRECTORY_SEPARATOR . $file, $file);
            }

            $zipArchive->close();

            return [
                'path' => $zipFile,
                'theme' => $theme,
            ];
        }

        return [];
    }

    /**
     * Create symbolic link for theme's Public directory.
     *
     * @param string $themeNamespace
     */
    public function createSymLink(string $themeNamespace): void
    {
        if (!is_dir(base_path('public/themes'))) {
            Storage::disk('ns-public')->makeDirectory('themes', 0755, true);
        }

        /**
         * checks if a public directory exists and create a
         * link for that directory
         */
        if (Storage::disk('ns-themes')->exists($themeNamespace . DIRECTORY_SEPARATOR . 'Public')) {
            $linkPath = base_path('public') . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . strtolower($themeNamespace);
            $targetPath = base_path('themes' . DIRECTORY_SEPARATOR . $themeNamespace . DIRECTORY_SEPARATOR . 'Public');

            // Check if link exists and is broken, then remove it
            if (is_link($linkPath)) {
                if ($this->isWindowsOS()) {
                    // Windows: Check if the target is accessible
                    if (!file_exists($linkPath) || !is_readable($linkPath)) {
                        $this->removeSymLink($themeNamespace);
                    }
                } else {
                    // Linux: Check if the symlink target exists
                    if (!file_exists(readlink($linkPath))) {
                        unlink($linkPath);
                    }
                }
            }

            /**
             * This creates symbolic links for the assets.
             */
            if (!is_link($linkPath) && !file_exists($linkPath)) {
                if ($this->isWindowsOS()) {
                    // Windows: Use junction (/J) for directory
                    $mode = 'J';
                    $command = "mklink /{$mode} \"{$linkPath}\" \"{$targetPath}\"";
                    exec($command, $output, $resultCode);

                    if ($resultCode !== 0) {
                        $errorMessage = sprintf(__('Failed to create symbolic link for theme "%s": %s'), $themeNamespace, implode("\n", $output));
                        Log::error($errorMessage);
                        throw new Exception($errorMessage);
                    }
                } else {
                    // Linux/Unix: Use symlink function
                    $result = @symlink($targetPath, $linkPath);

                    if (!$result) {
                        $errorMessage = sprintf(__('Failed to create symbolic link for theme "%s"'), $themeNamespace);
                        Log::error($errorMessage);
                        throw new Exception($errorMessage);
                    }
                }
            }
        }
    }

    /**
     * Remove symbolic link for theme.
     *
     * @param string $themeNamespace
     */
    public function removeSymLink(string $themeNamespace): void
    {
        $linkPath = base_path('public') . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . strtolower($themeNamespace);

        if (is_link($linkPath) || file_exists($linkPath)) {
            if ($this->isWindowsOS()) {
                if (is_dir($linkPath)) {
                    $command = "rmdir \"{$linkPath}\"";
                    exec($command, $output, $resultCode);

                    if ($resultCode !== 0) {
                        Log::warning("Failed to remove theme directory link: " . implode("\n", $output));
                    }
                } else {
                    $command = "del \"{$linkPath}\"";
                    exec($command, $output, $resultCode);

                    if ($resultCode !== 0) {
                        Log::warning("Failed to remove theme file link: " . implode("\n", $output));
                    }
                }
            } else {
                if (is_link($linkPath)) {
                    unlink($linkPath);
                }
            }
        }
    }

    /**
     * Check if running on Windows OS.
     *
     * @return bool
     */
    private function isWindowsOS(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}
