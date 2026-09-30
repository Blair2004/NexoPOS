<?php

namespace Tests\Unit;

use App\Support\PosLayout;
use PHPUnit\Framework\TestCase;

class PosLayoutTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_it_preserves_canonical_layouts(): void
    {
        $this->assertSame( PosLayout::Split, PosLayout::normalize( PosLayout::Split ) );
        $this->assertSame( PosLayout::Unified, PosLayout::normalize( PosLayout::Unified ) );
    }

    public function test_it_normalizes_missing_and_legacy_layouts_to_split(): void
    {
        $this->assertSame( PosLayout::Split, PosLayout::normalize( null ) );
        $this->assertSame( PosLayout::Split, PosLayout::normalize( 'grocery_shop' ) );
        $this->assertSame( PosLayout::Split, PosLayout::normalize( 'clothing_shop' ) );
    }
}
