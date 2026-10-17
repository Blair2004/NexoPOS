import assert from 'node:assert/strict';
import test from 'node:test';

import { setPosSetting, unsetPosSetting } from '../../resources/ts/libraries/pos-settings-state.js';

test( 'setting a POS value returns a new object without mutating current settings', () => {
    const currentSettings = { ns_pos_items_merge: false, barcode_search: true };
    const nextSettings = setPosSetting( currentSettings, 'ns_pos_items_merge', true );

    assert.notStrictEqual( nextSettings, currentSettings );
    assert.equal( currentSettings.ns_pos_items_merge, false );
    assert.equal( nextSettings.ns_pos_items_merge, true );
    assert.equal( nextSettings.barcode_search, true );
} );

test( 'unsetting a POS value returns a new object without mutating current settings', () => {
    const currentSettings = { ns_pos_items_merge: true, barcode_search: true };
    const nextSettings = unsetPosSetting( currentSettings, 'ns_pos_items_merge' );

    assert.notStrictEqual( nextSettings, currentSettings );
    assert.equal( currentSettings.ns_pos_items_merge, true );
    assert.equal( 'ns_pos_items_merge' in nextSettings, false );
    assert.equal( nextSettings.barcode_search, true );
} );
