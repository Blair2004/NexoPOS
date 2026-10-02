export function normalizePosLayout(layout) {
  return layout === 'unified' ? 'unified' : 'split';
}

export function resolvePosSection(layout, requestedSection, screen) {
  if (normalizePosLayout(layout) === 'unified') {
    return 'cart';
  }

  if (['cart', 'grid', 'both'].includes(requestedSection)) {
    return requestedSection;
  }

  return ['xs', 'sm'].includes(screen) ? 'grid' : 'both';
}
