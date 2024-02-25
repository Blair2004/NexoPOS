<?php

namespace Database\Seeders;

use App\Models\RewardSystem;
use Illuminate\Database\Seeder;

class RewardSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        return RewardSystem::factory()
            ->count( 20 )
            ->hasRules( 4 )
            ->hasCoupon( 1 )
            ->create();
    }
}
