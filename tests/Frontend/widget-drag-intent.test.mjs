import assert from 'node:assert/strict';
import test from 'node:test';

import { hasCrossedWidgetMidpoint, hasMovedBeyondReorderLock } from '../../resources/ts/components/widget-drag-intent.js';

const dragged = { left: 0, top: 0, width: 100, height: 100 };

test( 'waits for the pointer to cross a widget midpoint in every direction', () => {
    const cases = [
        { hovered: { left: 200, top: 0, width: 200, height: 100 }, before: [ 299, 50 ], after: [ 300, 50 ] },
        { hovered: { left: -300, top: 0, width: 200, height: 100 }, before: [ -199, 50 ], after: [ -200, 50 ] },
        { hovered: { left: 0, top: 200, width: 100, height: 400 }, before: [ 50, 399 ], after: [ 50, 400 ] },
        { hovered: { left: 0, top: -500, width: 100, height: 400 }, before: [ 50, -299 ], after: [ 50, -300 ] },
    ];

    for ( const dragCase of cases ) {
        assert.equal( hasCrossedWidgetMidpoint( {
            clientX: dragCase.before[0],
            clientY: dragCase.before[1],
        }, dragged, dragCase.hovered ), false );
        assert.equal( hasCrossedWidgetMidpoint( {
            clientX: dragCase.after[0],
            clientY: dragCase.after[1],
        }, dragged, dragCase.hovered ), true );
    }
} );

test( 'uses the dominant axis when widgets are diagonally separated', () => {
    const hovered = { left: 100, top: 300, width: 100, height: 100 };

    assert.equal( hasCrossedWidgetMidpoint( { clientX: 150, clientY: 349 }, dragged, hovered ), false );
    assert.equal( hasCrossedWidgetMidpoint( { clientX: 150, clientY: 350 }, dragged, hovered ), true );
} );

test( 'keeps the post-swap lock until the pointer moves a meaningful distance', () => {
    const lockPoint = { clientX: 300, clientY: 400 };

    assert.equal( hasMovedBeyondReorderLock( { clientX: 320, clientY: 420 }, lockPoint, 36 ), false );
    assert.equal( hasMovedBeyondReorderLock( { clientX: 336, clientY: 400 }, lockPoint, 36 ), true );
} );
