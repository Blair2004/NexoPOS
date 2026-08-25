<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if ( Schema::hasTable( 'nexopos_transactions_accounts' ) && ! Schema::hasColumn( 'nexopos_transactions_accounts', 'system_identifier' ) ) {
            Schema::table( 'nexopos_transactions_accounts', function ( Blueprint $table ) {
                $table->string( 'system_identifier' )->nullable()->after( 'uuid' );
            } );
        }

        if ( Schema::hasTable( 'nexopos_transactions_accounts' ) && ! Schema::hasIndex( 'nexopos_transactions_accounts', 'transactions_accounts_system_identifier_unique' ) ) {
            Schema::table( 'nexopos_transactions_accounts', function ( Blueprint $table ) {
                $table->unique( 'system_identifier', 'transactions_accounts_system_identifier_unique' );
            } );
        }

        if ( Schema::hasTable( 'nexopos_transactions_actions_rules' ) && ! Schema::hasColumn( 'nexopos_transactions_actions_rules', 'active' ) ) {
            Schema::table( 'nexopos_transactions_actions_rules', function ( Blueprint $table ) {
                $table->boolean( 'active' )->default( true )->after( 'locked' );
            } );
        }

        Schema::createIfMissing( 'nexopos_transactions_actions_rule_lines', function ( Blueprint $table ) {
            $table->id();
            $table->unsignedBigInteger( 'rule_id' );
            $table->unsignedBigInteger( 'account_id' )->nullable();
            $table->string( 'dynamic_account_role' )->nullable();
            $table->enum( 'effect', [ 'increase', 'decrease' ] );
            $table->string( 'amount_source' );
            $table->unsignedInteger( 'display_order' )->default( 0 );
            $table->timestamps();
            $table->index( [ 'rule_id', 'display_order' ], 'transaction_rule_lines_order_index' );
        } );

        Schema::createIfMissing( 'nexopos_accounting_journals', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'source_type' );
            $table->string( 'source_id' );
            $table->string( 'event' );
            $table->unsignedBigInteger( 'rule_id' )->nullable();
            $table->string( 'name' );
            $table->enum( 'status', [ 'posted', 'reversed' ] )->default( 'posted' );
            $table->integer( 'author_id' );
            $table->datetime( 'posted_at' );
            $table->timestamps();
            $table->unique( [ 'source_type', 'source_id', 'event' ], 'accounting_journals_source_event_unique' );
        } );

        if ( Schema::hasTable( 'nexopos_transactions_histories' ) && ! Schema::hasColumn( 'nexopos_transactions_histories', 'journal_id' ) ) {
            Schema::table( 'nexopos_transactions_histories', function ( Blueprint $table ) {
                $table->unsignedBigInteger( 'journal_id' )->nullable()->after( 'rule_id' );
                $table->index( 'journal_id', 'transactions_histories_journal_index' );
            } );
        }

        if ( Schema::hasTable( 'nexopos_payments_types' ) && ! Schema::hasColumn( 'nexopos_payments_types', 'accounting_account_id' ) ) {
            Schema::table( 'nexopos_payments_types', function ( Blueprint $table ) {
                $table->unsignedBigInteger( 'accounting_account_id' )->nullable()->after( 'readonly' );
            } );
        }

        if ( Schema::hasTable( 'nexopos_payments_types' ) && ! Schema::hasColumn( 'nexopos_payments_types', 'accounting_incoming_effect' ) ) {
            Schema::table( 'nexopos_payments_types', function ( Blueprint $table ) {
                $table->enum( 'accounting_incoming_effect', [ 'increase', 'decrease' ] )->nullable()->after( 'accounting_account_id' );
            } );
        }
    }

    public function down(): void
    {
        if ( Schema::hasTable( 'nexopos_transactions_histories' ) && Schema::hasIndex( 'nexopos_transactions_histories', 'transactions_histories_journal_index' ) ) {
            Schema::table( 'nexopos_transactions_histories', function ( Blueprint $table ) {
                $table->dropIndex( 'transactions_histories_journal_index' );
            } );
        }

        foreach ( [
            'nexopos_transactions_histories' => [ 'journal_id' ],
            'nexopos_payments_types' => [ 'accounting_incoming_effect', 'accounting_account_id' ],
            'nexopos_transactions_actions_rules' => [ 'active' ],
        ] as $tableName => $columns ) {
            foreach ( $columns as $column ) {
                if ( Schema::hasTable( $tableName ) && Schema::hasColumn( $tableName, $column ) ) {
                    Schema::table( $tableName, function ( Blueprint $table ) use ( $column ) {
                        $table->dropColumn( $column );
                    } );
                }
            }
        }

        Schema::dropIfExists( 'nexopos_accounting_journals' );
        Schema::dropIfExists( 'nexopos_transactions_actions_rule_lines' );

        if ( Schema::hasTable( 'nexopos_transactions_accounts' ) && Schema::hasIndex( 'nexopos_transactions_accounts', 'transactions_accounts_system_identifier_unique' ) ) {
            Schema::table( 'nexopos_transactions_accounts', function ( Blueprint $table ) {
                $table->dropUnique( 'transactions_accounts_system_identifier_unique' );
            } );
        }

        if ( Schema::hasTable( 'nexopos_transactions_accounts' ) && Schema::hasColumn( 'nexopos_transactions_accounts', 'system_identifier' ) ) {
            Schema::table( 'nexopos_transactions_accounts', function ( Blueprint $table ) {
                $table->dropColumn( 'system_identifier' );
            } );
        }
    }
};
