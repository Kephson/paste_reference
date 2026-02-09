import { vi } from 'vitest';

// Global TYPO3 mock
global.TYPO3 = {
  lang: {
    'tx_paste_reference_js.copyfrompage': 'Copy from another page',
    'tx_paste_reference_js.modal.labels.no_title': 'No title',
    'paste.modal.title.paste': 'Paste record',
    'paste.modal.button.cancel': 'Cancel',
    'paste.modal.button.paste': 'Move',
    'paste.modal.title.error': 'Paste Error',
    'paste.modal.button.ok': 'OK',
    'tx_paste_reference_js.modal.pastecopy': 'How do you want to paste that clipboard content here?',
    'tx_paste_reference_js.modal.button.pastecopy': 'Paste as copy',
    'tx_paste_reference_js.modal.button.pastereference': 'Paste as reference',
    'paste.modal.paste': 'Do you want to move the record to this position?',
    'tx_paste_reference_js.newcontentelementheader': 'New Content Element'
  },
  Severity: {
    info: 0,
    notice: 1,
    ok: 2,
    warning: 3,
    error: 4,
    getCssClass: (severity) => {
      const classes = ['info', 'notice', 'ok', 'warning', 'error'];
      return classes[severity] || 'info';
    }
  },
  settings: {
    Clipboard: {
      moduleUrl: '/typo3/clipboard'
    }
  }
};

global.top = {
  TYPO3: global.TYPO3,
  browserUrl: 'http://localhost/typo3/wizard/record/browse',
  pasteReferenceAllowed: 1
};

// Mock console methods to reduce noise in tests
global.console = {
  ...console,
  log: vi.fn(),
  warn: vi.fn(),
  error: vi.fn()
};

// Mock window.location
Object.defineProperty(window, 'location', {
  value: {
    reload: vi.fn(),
    hash: ''
  },
  writable: true
});

// Mock MessageEvent for modal communication tests
global.MessageEvent = class MessageEvent extends Event {
  constructor(type, eventInitDict = {}) {
    super(type, eventInitDict);
    this.data = eventInitDict.data || {};
    this.origin = eventInitDict.origin || 'http://localhost';
  }
};