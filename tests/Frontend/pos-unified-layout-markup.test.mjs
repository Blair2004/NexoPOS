import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const cart = readFileSync( new URL( '../../resources/ts/pages/dashboard/pos/ns-pos-cart.vue', import.meta.url ), 'utf8' );
const pos = readFileSync( new URL( '../../resources/ts/pages/dashboard/pos/ns-pos.vue', import.meta.url ), 'utf8' );

test( 'unified mode hides the cart and product tab strip', () => {
    assert.match( cart, /visibleSection === 'cart' && ! isUnified/ );
} );

test( 'unified entry controls are the first cart toolbox controls', () => {
    const toolboxStart = cart.indexOf( 'id="cart-toolbox"' );
    const toolbar = cart.indexOf( '<ns-pos-product-entry-toolbar', toolboxStart );
    const injectedControls = cart.indexOf( 'v-for="component of cartHeaderButtons"', toolboxStart );

    assert.notEqual( toolboxStart, -1 );
    assert.ok( toolbar > toolboxStart );
    assert.ok( injectedControls > toolbar );
} );

test( 'the POS shell no longer renders a toolbar above the cart', () => {
    assert.doesNotMatch( pos, /<ns-pos-product-entry-toolbar/ );
} );
