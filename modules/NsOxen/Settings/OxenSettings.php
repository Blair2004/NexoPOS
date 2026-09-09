<?php

namespace Modules\NsOxen\Settings;

use App\Services\SettingsPage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as FacadesView;

class OxenSettings extends SettingsPage
{
    public const IDENTIFIER = "ns-oxen-settings";

    public const AUTOLOAD = true;

    public function getView(): View
    {
        return FacadesView::make( "NsOxen::settings.index", [
            'title' => __m( 'Oxen Settings', 'NsOxen' ),
            'description' => __m( 'Configure the OpenAI connection and assistant safeguards for this installation.', 'NsOxen' ),
        ]);
    }
}
