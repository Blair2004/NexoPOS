<?php

namespace App\Http\Controllers;

use App\Events\AfterMigrationExecutedEvent;
use App\Services\ModulesService;
use App\Services\UpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LocalizationController extends Controller
{
    public function __construct(
        public ModulesService $modulesService
    )
    {
        // ...
    }

    public function getLocalization(string $locale)
    {
        $cacheFilePath = storage_path("lang-cache/$locale.json");
        if (file_exists($cacheFilePath)) {
            return response()->file($cacheFilePath);
        }

        $lang = $this->compileLanguageFile($locale);

        mkdir(dirname($cacheFilePath), permissions: 0755, recursive: true);
        file_put_contents($cacheFilePath, json_encode($lang));

        return $lang;
    }


    /**
     * @param string $locale
     * @return array|mixed
     */
    public function compileLanguageFile(string $locale): mixed
    {
        $lang = [];

        if (Storage::disk('ns')->exists("lang/$locale.json")) {
            $lang = $this->readLanguageJson("lang/$locale.json");
        }

        $activeModules = $this->modulesService->getEnabled();

        foreach ($activeModules as $module) {
            if (
                isset($module['langFiles']) &&
                isset($module['langFiles'][$locale]) &&
                Storage::disk('ns-modules')->exists($module['langFiles'][$locale])
            ) {
                $moduleLang = $this->readLanguageJson($module['langFiles'][$locale], $module['namespace']);
                $lang = array_merge($lang, $moduleLang);
            }
        }

        return $lang;
    }


    /**
     * @param string $file
     * @param string|null $namespace
     * @return mixed
     */
    private function readLanguageJson(string $file, string $namespace = null)
    {
        $disk = empty($namespace) ? 'ns' : 'ns-modules';
        $contents = Storage::disk($disk)->get($file);
        $locales = json_decode($contents, true);

        if (empty($namespace)) {
            return $locales;
        } else {
            return collect($locales)->mapWithKeys(function ($value, $key) use ($namespace) {
                return ["$namespace.$key" => $value];
            })->toArray();
        }
    }
}
