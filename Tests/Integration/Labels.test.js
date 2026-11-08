import { beforeEach, describe, expect, it } from 'vitest';
import labels from '../../Resources/Public/JavaScript/labels.js';

describe('labels', () => {
  beforeEach(() => {
    TYPO3.lang = { header: 'Reference: {title}', plain: 'Plain' };
  });

  it('replaces placeholders', () => {
    expect(labels.get('header', { title: 'First' })).toBe('Reference: First');
  });

  it('returns labels without placeholders', () => {
    expect(labels.get('plain')).toBe('Plain');
  });

  it('falls back to the key for unknown labels', () => {
    expect(labels.get('unknown')).toBe('unknown');
  });
});
