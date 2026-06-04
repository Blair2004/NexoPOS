<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Tools\Tool;

class UpdateSettingsTool extends Tool
{
    public string $name = 'update_settings';

    public string $description = 'Updates NexoPOS configuration settings via ns()->option->set(key, value).';

    public function schema(): array
    {
        return [
            'settings' => [
                'type' => 'object',
                'description' => 'A key-value pair of settings to update (e.g. {"ns_store_name": "My Shop"}).',
                'additionalProperties' => true,
            ]
        ];
    }

    public function handle(array $parameters): array
    {
        if (empty($parameters['settings']) || !is_array($parameters['settings'])) {
            return $this->error('The settings parameter must be a non-empty object containing key-value pairs.');
        }

        $applied = [];

        foreach ($parameters['settings'] as $key => $value) {
            ns()->option->set($key, $value);
            $applied[] = $key;
        }

        return [
            'applied_keys' => $applied,
            'message' => 'Settings updated successfully.'
        ];
    }
}
