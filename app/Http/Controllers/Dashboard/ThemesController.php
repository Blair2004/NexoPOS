<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Services\DateService;
use App\Services\ThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class ThemesController extends DashboardController
{
    public function __construct(
        protected ThemeService $themes,
        protected DateService $dateService
    ) {
        $this->middleware(function ($request, $next) {
            ns()->restrict(['manage.themes']);

            return $next($request);
        });
    }

    public function listThemes($page = '')
    {
        return View::make('pages.dashboard.themes.list', [
            'title' => __('Themes List'),
            'description' => __('List all available themes.'),
        ]);
    }

    public function downloadTheme($identifier)
    {
        ns()->restrict(['manage.themes']);

        $theme = $this->themes->get($identifier);
        $download = $this->themes->extract($identifier);
        $relativePath = substr($download['path'], strlen(base_path()));

        return Storage::disk('ns')->download($relativePath, Str::slug($theme['name']) . '-' . $theme['version'] . '.zip');
    }

    /**
     * Get themes using various statuses
     *
     * @param string status
     * @return array of themes
     */
    public function getThemes($argument = '')
    {
        switch ($argument) {
            case 'enabled':
                $enabled = $this->themes->getEnabled();
                $list = $enabled ? [$enabled] : [];
                break;
            case 'disabled':
                $list = $this->themes->getDisabled();
                break;
            case 'invalid':
                $list = $this->themes->getInvalid();
                break;
            case '':
            default:
                $list = $this->themes->get();
                break;
        }

        $enabled = $this->themes->getEnabled();

        return [
            'themes' => $list,
            'total_enabled' => $enabled ? 1 : 0,
            'total_disabled' => count($this->themes->getDisabled()),
            'total_invalid' => count($this->themes->getInvalid()),
        ];
    }

    /**
     * @param string theme identifier
     * @return array operation response
     */
    public function disableTheme($argument)
    {
        return $this->themes->disable($argument);
    }

    /**
     * @param string theme identifier
     * @return array operation response
     */
    public function enableTheme($argument)
    {
        return $this->themes->enable($argument);
    }

    /**
     * @param string theme identifier
     * @return array operation response
     */
    public function deleteTheme($argument)
    {
        return $this->themes->delete($argument);
    }

    public function showUploadTheme()
    {
        return View::make('pages.dashboard.themes.upload', [
            'title' => __('Upload A Theme'),
            'description' => __('Extends NexoPOS with custom themes.'),
        ]);
    }

    /**
     * Upload a theme. Expect a "theme" provided as a file input
     *
     * @return Json|Redirect response
     */
    public function uploadTheme(Request $request)
    {
        $request->validate([
            'theme' => 'required|file|mimes:zip',
        ]);

        $result = $this->themes->upload($request->file('theme'));

        if ($request->expectsJson()) {
            return response()->json($result);
        } else {
            /**
             * if the theme upload was successful
             */
            if ($result['status'] === 'success') {
                return redirect(ns()->route('ns.dashboard.themes-list'))->with($result);
            } else {
                $validator = Validator::make($request->all(), []);
                $validator->errors()->add('theme', $result['message']);

                return redirect(ns()->route('ns.dashboard.themes-upload'))->withErrors($validator);
            }
        }
    }

    public function createSymlink(Request $request)
    {
        $theme = $request->input('theme');

        if (!$theme) {
            return response()->json([
                'status' => 'error',
                'message' => __('Theme not specified.'),
            ], 400);
        }

        $this->themes->createSymlink($theme['namespace']);

        return response()->json([
            'status' => 'success',
            'message' => __('Symbolic link created successfully.'),
        ]);
    }
}
