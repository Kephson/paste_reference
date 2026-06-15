import { vi } from 'vitest';

export default {
  process: vi.fn(() => Promise.resolve({ hasErrors: false }))
};