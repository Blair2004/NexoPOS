<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\RewardSystem;
use App\Models\RewardSystemRule;

class RewardSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory( App\Models\RewardSystem::class, 50 )->create()->each( function( $reward ) {
            $reward->rules()->saveMany(
                factory( App\Models\RewardSystemRule::class, 4 )->make()
            );
        });
    }
}
