<?php

namespace Modules\NsOxen\Services;

use App\Services\ModulesService;

class TrustedSystemContext
{
    public function __construct(
        private readonly StoreClock $clock,
        private readonly ModulesService $modulesService,
    ) {}

    /** @return array{current_datetime: array{store: string, utc: string, timezone: string}, nexopos_version: string, modules: list<array{namespace: string, name: string, version: string, enabled: bool}>} */
    public function toArray(): array
    {
        $storeNow = $this->clock->now();
        $modules = collect( $this->modulesService->get() )
            ->map( static fn ( array $module, string $namespace ): array => [
                'namespace' => (string) ( $module['namespace'] ?? $namespace ),
                'name' => (string) ( $module['name'] ?? $namespace ),
                'version' => (string) ( $module['version'] ?? '' ),
                'enabled' => (bool) ( $module['enabled'] ?? false ),
            ] )
            ->sortBy( 'namespace' )
            ->values()
            ->all();

        return [
            'current_datetime' => [
                'store' => $storeNow->toIso8601String(),
                'utc' => $storeNow->copy()->utc()->toIso8601String(),
                'timezone' => $storeNow->getTimezone()->getName(),
            ],
            'nexopos_version' => (string) config( 'nexopos.version' ),
            'modules' => $modules,
        ];
    }
}
