import DocumentService from '@typo3/core/document-service.js';
import '@ehaerer/paste-reference/element/paste-reference-toolbar.js';

/**
 * Mounts one <paste-reference-toolbar> into every "new content element" action bar.
 * Instantiated by ModifyPageLayoutContentListener, which provides the configuration.
 *
 * @exports @ehaerer/paste-reference/paste-toolbar
 */
export default class PasteToolbar {
  constructor(config) {
    DocumentService.ready().then(() => {
      document.querySelectorAll('.t3js-page-new-ce').forEach((actions) => {
        if (actions.querySelector('paste-reference-toolbar') === null) {
          const toolbar = document.createElement('paste-reference-toolbar');
          toolbar.config = config;
          actions.append(toolbar);
        }
      });
    });
  }
}
