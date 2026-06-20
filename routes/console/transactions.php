<?php

use App\Jobs\TriggerRecurringTransactionJob;
use App\Models\Transaction;
use Illuminate\Support\Facades\Schedule;

Transaction::recurring()->where( 'active', true )->where( 'occurrence', Transaction::OCCURRENCE_EVERY_X_DAYS )->get()->each( function ( $transaction ) {
    Schedule::job( new TriggerRecurringTransactionJob( $transaction ) )->cron( '0 0 */' . $transaction->occurrence_value . ' * *' );
} );

Transaction::recurring()->where( 'active', true )->where( 'occurrence', Transaction::OCCURRENCE_EVERY_X_MINUTES )->get()->each( function ( $transaction ) {
    Schedule::job( new TriggerRecurringTransactionJob( $transaction ) )->cron( '*/' . $transaction->occurrence_value . ' * * * *' );
} );

Transaction::recurring()->where( 'active', true )->where( 'occurrence', Transaction::OCCURRENCE_EVERY_X_HOURS )->get()->each( function ( $transaction ) {
    Schedule::job( new TriggerRecurringTransactionJob( $transaction ) )->cron( '0 */' . $transaction->occurrence_value . ' * * *' );
} );

Transaction::recurring()->where( 'active', true )->where( 'occurrence', Transaction::OCCURRENCE_START_OF_MONTH )->get()->each( function ( $transaction ) {
    Schedule::job( new TriggerRecurringTransactionJob( $transaction ) )->monthlyOn( $transaction->occurrence_value, '00:01' );
} );

Transaction::recurring()->where( 'active', true )->where( 'occurrence', Transaction::OCCURRENCE_END_OF_MONTH )->get()->each( function ( $transaction ) {
    Schedule::job( new TriggerRecurringTransactionJob( $transaction ) )->monthlyOn( now()->copy()->endOfMonth()->subDays( $transaction->occurrence_value )->day, '00:01' );
} );

Transaction::recurring()->where( 'active', true )->where( 'occurrence', Transaction::OCCURRENCE_X_BEFORE_MONTH_ENDS )->get()->each( function ( $transaction ) {
    Schedule::call( function () use ( $transaction ) {
        if ( now()->copy()->addDays( $transaction->occurrence_value )->isLastOfMonth() ) {
            dispatch( new TriggerRecurringTransactionJob( $transaction ) );
        }
    } )->daily();
} );

Transaction::recurring()->where( 'active', true )->where( 'occurrence', Transaction::OCCURRENCE_X_AFTER_MONTH_STARTS )->get()->each( function ( $transaction ) {
    Schedule::call( function () use ( $transaction ) {
        if ( now()->copy()->addDays( $transaction->occurrence_value )->isFirstOfMonth() ) {
            dispatch( new TriggerRecurringTransactionJob( $transaction ) );
        }
    } )->daily();
} );

Transaction::recurring()->where( 'active', true )->where( 'occurrence', Transaction::OCCURRENCE_SPECIFIC_DAY )->get()->each( function ( $transaction ) {
    Schedule::job( new TriggerRecurringTransactionJob( $transaction ) )->monthlyOn( $transaction->occurrence_value, '00:01' );
} );

Transaction::recurring()->where( 'active', true )->where( 'occurrence', Transaction::OCCURRENCE_MIDDLE_OF_MONTH )->get()->each( function ( $transaction ) {
    Schedule::job( new TriggerRecurringTransactionJob( $transaction ) )->monthlyOn( 15, '00:01' );
} );