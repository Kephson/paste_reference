import { describe, expect, it, vi } from 'vitest';

vi.mock('@typo3/backend/ajax-data-handler.js', () => ({ default: { process: vi.fn() } }));
vi.mock('@ehaerer/paste-reference/labels.js', () => ({
  default: {
    get: (key, args) => (key.endsWith('no_title') ? `Reference: [${args.uid}]` : `Reference: ${args.title}`),
  },
}));

const { buildCopyCommand, buildReferenceData } = await import('../../Resources/Public/JavaScript/command.js');

const firstRecord = (params) => Object.values(params.data.tt_content)[0];

describe('command', () => {
  it('builds a shortcut datamap with an explicit language', () => {
    const params = buildReferenceData(12, 'Title', { target: -5, colPos: 1, language: 0, containerParent: 0 });

    expect(Object.keys(params.data.tt_content)[0]).toMatch(/^NEW/);
    expect(firstRecord(params)).toEqual({
      pid: -5,
      CType: 'shortcut',
      records: 'tt_content_12',
      header: 'Reference: Title',
      colPos: 1,
      sys_language_uid: 0,
    });
  });

  it('adds the container parent only when present', () => {
    const params = buildReferenceData(12, 'Title', { target: 7, colPos: 101, language: 1, containerParent: 3 });

    expect(firstRecord(params).tx_container_parent).toBe(3);
  });

  it('falls back to the uid for an empty title', () => {
    const params = buildReferenceData(12, '', { target: 7, colPos: 0, language: 0, containerParent: 0 });

    expect(firstRecord(params).header).toBe('Reference: [12]');
  });

  it('keeps -1 of the original when pasting into the default language', () => {
    const params = buildReferenceData(12, 'Title', { target: 7, colPos: 0, language: 0, containerParent: 0 }, -1);

    expect(firstRecord(params).sys_language_uid).toBe(-1);
  });

  it('uses the target language when pasting an element for all languages into a translation', () => {
    const params = buildReferenceData(12, 'Title', { target: 7, colPos: 0, language: 1, containerParent: 0 }, -1);

    expect(firstRecord(params).sys_language_uid).toBe(1);
  });

  it('uses the target language for an original of the default language', () => {
    const params = buildReferenceData(12, 'Title', { target: 7, colPos: 0, language: 0, containerParent: 0 }, 0);

    expect(firstRecord(params).sys_language_uid).toBe(0);
  });

  it('builds a paste copy command', () => {
    expect(buildCopyCommand(12, { target: -5, colPos: 1, language: 2, containerParent: 3 })).toEqual({
      cmd: {
        tt_content: {
          12: {
            copy: {
              action: 'paste',
              target: -5,
              update: { colPos: 1, sys_language_uid: 2, tx_container_parent: 3 },
            },
          },
        },
      },
    });
  });
});
