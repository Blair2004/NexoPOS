<?php

use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('nexopos_transacations')) {
            Schema::table('nexopos_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('nexopos_transactions', 'type')) {
                    $table->string('type')->nullable();
                }
            });

            Transaction::get()->each(function ($transaction) {
                if ($transaction->recurring) {
                    $transaction->type = Transaction::TYPE_RECURRING;
                } elseif ($transaction->group_id > 0) {
                    $transaction->type = Transaction::TYPE_ENTITY;
                } else {
                    $transaction->type = Transaction::TYPE_DIRECT;
                }

                $transaction->save();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nexopos_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('nexopos_transactions', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
