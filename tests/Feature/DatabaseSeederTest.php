<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RewardSystem;
use App\Models\TaxGroup;
use App\Models\Unit;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    public function test_database_seeder_populates_the_installed_application(): void
    {
        $this->seed( DatabaseSeeder::class );

        $this->assertGreaterThan( 0, RewardSystem::count() );
        $this->assertGreaterThan( 0, Unit::count() );
        $this->assertSame( Unit::count(), Unit::whereNotNull( 'identifier' )->count() );
        $this->assertGreaterThan( 0, TaxGroup::count() );
        $this->assertGreaterThan( 0, Product::count() );
    }
}
