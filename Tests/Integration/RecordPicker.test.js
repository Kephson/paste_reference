import { beforeEach, describe, expect, it, vi } from 'vitest';

const get = vi.fn();
vi.mock('@typo3/core/ajax/ajax-request.js', () => ({
  default: class {
    withQueryArguments(args) {
      this.args = args;
      return this;
    }

    get() {
      return get(this.args);
    }
  },
}));

const { fetchRecordInfo, getPasteActions } = await import('../../Resources/Public/JavaScript/record-picker.js');

describe('fetchRecordInfo', () => {
  beforeEach(() => {
    get.mockReset();
    TYPO3.settings.ajaxUrls = { paste_reference_record_info: '/record-info' };
  });

  it('returns the record data including the language of the original', async () => {
    const info = { uid: 12, CType: 'text', sys_language_uid: -1, title: 'Title' };
    get.mockImplementation(async () => ({ resolve: async () => info }));

    await expect(fetchRecordInfo(12)).resolves.toEqual(info);
    expect(get).toHaveBeenCalledWith({ uid: 12 });
  });

  it('returns null when the request fails', async () => {
    get.mockImplementation(async () => {
      throw new Error('denied');
    });

    await expect(fetchRecordInfo(12)).resolves.toBeNull();
  });
});

describe('getPasteActions', () => {
  const allowOnly = (...types) => (type) => types.includes(type);

  it('offers copy only when the CType is allowed in the target', () => {
    expect(getPasteActions('html', true, allowOnly('shortcut'))).toEqual({ copy: false, reference: true });
  });

  it('offers reference only when shortcut is allowed in the target', () => {
    expect(getPasteActions('text', true, allowOnly('text'))).toEqual({ copy: true, reference: false });
  });

  it('offers no reference without the permission for shortcut elements', () => {
    expect(getPasteActions('text', false, allowOnly('text', 'shortcut'))).toEqual({ copy: true, reference: false });
  });
});
