<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\UnitGroup;
use Tests\TestCase;

class UnitFactoryTest extends TestCase
{
    public function test_factory_creates_a_unit_with_a_unique_identifier(): void
    {
        $unitGroup = UnitGroup::factory()->create();
        $units = Unit::factory()
            ->count( 2 )
            ->for( $unitGroup, 'group' )
            ->create();

        $this->assertModelExists( $units );
        $this->assertNotEmpty( $units[0]->identifier );
        $this->assertNotEmpty( $units[1]->identifier );
        $this->assertNotSame( $units[0]->identifier, $units[1]->identifier );
    }
}
