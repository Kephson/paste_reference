import { vi } from 'vitest';

const Modal = {
  show: vi.fn(),
  advanced: vi.fn(),
  loadUrl: vi.fn(),
  types: {
    iframe: 'iframe'
  },
  sizes: {
    large: 'large'
  }
};

export default Modal;
export { Modal as ModalElement };