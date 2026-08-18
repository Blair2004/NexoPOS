<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\BroadcastServiceProvider;
use App\Providers\CrudServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\FormsProvider;
use App\Providers\GuideServiceProvider;
use App\Providers\LocalizationServiceProvider;
use App\Providers\ModulesServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\SettingsPageProvider;
use App\Providers\TelescopeServiceProvider;
use App\Providers\WidgetsServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    BroadcastServiceProvider::class,
    CrudServiceProvider::class,
    EventServiceProvider::class,
    FormsProvider::class,
    LocalizationServiceProvider::class,
    ModulesServiceProvider::class,
    RouteServiceProvider::class,
    SettingsPageProvider::class,
    TelescopeServiceProvider::class,
    WidgetsServiceProvider::class,
    GuideServiceProvider::class,
];
