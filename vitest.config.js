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
      '@typo3/core/ajax/ajax-request.js': './Tests/mocks/ajax-request.js',
      '@typo3/core/document-service.js': './Tests/mocks/document-service.js',
      '@typo3/backend/ajax-data-handler.js': './Tests/mocks/data-handler.js',
      '@typo3/backend/modal.js': './Tests/mocks/modal.js',
      '@typo3/backend/severity.js': './Tests/mocks/severity.js',
      '@typo3/backend/element/icon-element.js': './Tests/mocks/icon-element.js',
      '@typo3/backend/enum/severity.js': './Tests/mocks/severity-enum.js',
      '@typo3/backend/utility/message-utility.js': './Tests/mocks/message-utility.js',
      '@typo3/core/event/regular-event.js': './Tests/mocks/regular-event.js',
      '@typo3/backend/broadcast-service.js': './Tests/mocks/broadcast-service.js',
      '@typo3/backend/broadcast-message.js': './Tests/mocks/broadcast-message.js',
      '@typo3/backend/layout-module/drag-drop.js': './Tests/mocks/layout-drag-drop.js',
      '@typo3/backend/layout-module/paste.js': './Tests/mocks/layout-paste.js',
      '@ehaerer/paste-reference/paste-reference-drag-drop.js': './Resources/Public/JavaScript/paste-reference-drag-drop.js',
      '@ehaerer/paste-reference/paste-reference-draggable.js': './Resources/Public/JavaScript/paste-reference-draggable.js'
    }
  }
});