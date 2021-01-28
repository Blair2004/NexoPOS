<?php
namespace Database\Seeders;

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
        return RewardSystem::factory()
            ->count(20)
            ->hasRules(4)
            ->hasCoupon(1)
            ->create();
    }
}
