import { vi } from 'vitest';

export default class RegularEvent {
  constructor(eventType, handler) {
    this.eventType = eventType;
    this.handler = handler;
  }

  delegateTo(element, selector) {
    // Mock event delegation
    return this;
  }
}