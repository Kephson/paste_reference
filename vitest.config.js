import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./Tests/setup.js'],
    include: ['Tests/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}'],
    exclude: ['node_modules', 'dist', '.git'],
    testTimeout: 10000,
    hookTimeout: 10000
  },
  resolve: {
    alias: {
      '@typo3/backend/ajax-data-handler.js': new URL('./Tests/mocks/data-handler.js', import.meta.url).pathname,
      '@ehaerer/paste-reference/labels.js': new URL('./Tests/mocks/labels.js', import.meta.url).pathname,
      '@typo3/core/ajax/ajax-request.js': new URL('./Tests/mocks/ajax-request.js', import.meta.url).pathname,
      '@typo3/core/document-service.js': './Tests/mocks/document-service.js',
      '@typo3/backend/modal.js': new URL('./Tests/mocks/modal.js', import.meta.url).pathname,
      '@typo3/backend/severity.js': './Tests/mocks/severity.js',
      '@typo3/backend/element/icon-element.js': './Tests/mocks/icon-element.js',
      '@typo3/backend/enum/severity.js': './Tests/mocks/severity-enum.js',
      '@typo3/backend/utility/message-utility.js': new URL('./Tests/mocks/message-utility.js', import.meta.url).pathname,
      '@typo3/core/event/regular-event.js': './Tests/mocks/regular-event.js',
      '@typo3/backend/broadcast-service.js': './Tests/mocks/broadcast-service.js',
      '@typo3/backend/broadcast-message.js': './Tests/mocks/broadcast-message.js'
    }
  }
});