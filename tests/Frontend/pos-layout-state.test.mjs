import assert from 'node:assert/strict';
import test from 'node:test';

import { normalizePosLayout, resolvePosSection } from '../../resources/ts/libraries/pos-layout-state.js';

test('normalizes missing and legacy layouts to split', () => {
  assert.equal(normalizePosLayout(undefined), 'split');
  assert.equal(normalizePosLayout('grocery_shop'), 'split');
  assert.equal(normalizePosLayout('clothing_shop'), 'split');
  assert.equal(normalizePosLayout('unified'), 'unified');
});

test('split layout keeps responsive defaults and explicit section changes', () => {
  assert.equal(resolvePosSection('split', null, 'xs'), 'grid');
  assert.equal(resolvePosSection('split', null, 'lg'), 'both');
  assert.equal(resolvePosSection('split', 'cart', 'lg'), 'cart');
  assert.equal(resolvePosSection('split', 'grid', 'lg'), 'grid');
});

test('unified layout always resolves to cart', () => {
  for (const section of [null, 'cart', 'grid', 'both']) {
    assert.equal(resolvePosSection('unified', section, 'xs'), 'cart');
    assert.equal(resolvePosSection('unified', section, 'xl'), 'cart');
  }
});
