<?php

namespace Database\Seeders;

use App\Models\Tax;
use App\Models\TaxGroup;
use App\Models\User;
use Illuminate\Database\Seeder;

class FirstExampleTaxesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $author = $author = User::get()->map( fn( $user ) => $user->id )
            ->shuffle()
            ->first();

        $group = new TaxGroup;
        $group->name = 'GST (7.5%)';
        $group->author = $author;
        $group->save();

        $tax = new Tax;
        $tax->name = 'SGST (5%)';
        $tax->rate = 5;
        $tax->author = $author;
        $tax->tax_group_id = $group->id;
        $tax->save();

        $tax = new Tax;
        $tax->name = 'IGST (2.5%)';
        $tax->rate = 2.5;
        $tax->author = $author;
        $tax->tax_group_id = $group->id;
        $tax->save();
    }
}
