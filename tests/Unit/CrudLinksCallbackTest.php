<?php

namespace Tests\Unit;

use App\Classes\CrudTable;
use App\Services\CrudService;
use Tests\TestCase;

final class CrudLinksCallbackTest extends TestCase
{
    public function test_crud_link_callbacks_resolve_submit_urls_from_the_entry(): void
    {
        $crud = new class extends CrudService
        {
            public const IDENTIFIER = 'test.callback-links';

            protected $namespace = 'test.callback-links';

            protected $permissions = [
                'read' => true,
                'update' => true,
            ];

            public function getLabels(): array
            {
                return [
                    'create_title' => 'Create',
                    'create_description' => 'Create description',
                    'edit_title' => 'Edit',
                    'edit_description' => 'Edit description',
                ];
            }

            public function getLinks(): array
            {
                return CrudTable::links(
                    list: '/items',
                    create: '/items/create',
                    edit: '/items/{id}/edit',
                    post: static fn (): string => '/api/items',
                    put: static fn ( object $entry ): string => '/api/items/' . $entry->uuid,
                );
            }
        };

        $entry = (object) [
            'id' => 42,
            'uuid' => 'item-uuid',
        ];

        $createConfig = $crud::getFormConfig();
        $editConfig = $crud::getFormConfig( entry: $entry );

        $this->assertSame( '/api/items', $createConfig['submitUrl'] );
        $this->assertSame( '/api/items/item-uuid', $editConfig['submitUrl'] );
        $this->assertIsCallable( $crud->getLinks()['put'] );
    }
}
