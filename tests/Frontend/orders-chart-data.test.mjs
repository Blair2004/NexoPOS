import assert from 'node:assert/strict';
import test from 'node:test';

import {
    areaPath,
    chartPoints,
    normalizeWeeklyReport,
    percentageChange,
    smoothLinePath,
} from '../../resources/ts/widgets/orders-chart-data.js';

test( 'normalizes sales amounts and order counts for both weeks', () => {
    const report = normalizeWeeklyReport( [ {
        label: 'Monday',
        current: {
            entries: [
                { day_paid_orders: '12.50', day_paid_orders_count: 2 },
                { day_paid_orders: 7.5, day_paid_orders_count: '3' },
            ],
        },
        previous: {
            entries: [ { day_paid_orders: '10', day_paid_orders_count: 1 } ],
        },
    }, {
        label: 'Tuesday',
    } ] );

    assert.deepEqual( report[0], {
        label: 'Monday',
        shortLabel: 'Mon',
        current: { amount: 20, count: 5 },
        previous: { amount: 10, count: 1 },
    } );
    assert.deepEqual( report[1].current, { amount: 0, count: 0 } );
    assert.deepEqual( report[1].previous, { amount: 0, count: 0 } );
} );

test( 'calculates day-over-day comparison without dividing by zero', () => {
    assert.equal( percentageChange( 125, 100 ), 25 );
    assert.equal( percentageChange( 75, 100 ), -25 );
    assert.equal( percentageChange( 0, 0 ), 0 );
    assert.equal( percentageChange( 10, 0 ), null );
} );

test( 'builds bounded chart points and smooth line and area paths', () => {
    const padding = { top: 10, right: 10, bottom: 20, left: 30 };
    const points = chartPoints( [ 0, 50, 100 ], 300, 160, padding, 100 );

    assert.deepEqual( points, [
        { x: 30, y: 140 },
        { x: 160, y: 75 },
        { x: 290, y: 10 },
    ] );
    assert.match( smoothLinePath( points ), /^M 30 140 Q / );
    assert.match( areaPath( points, 140 ), /L 290 140 L 30 140 Z$/ );
} );
