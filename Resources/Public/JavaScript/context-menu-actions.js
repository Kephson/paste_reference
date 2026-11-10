import Modal from '@typo3/backend/modal.js';
import Severity from '@typo3/backend/severity.js';
import Viewport from '@typo3/backend/viewport.js';
import { pasteAsReference } from '@ehaerer/paste-reference/command.js';

/**
 * JavaScript to handle PasteReference related actions for Contextmenu
 * @exports @ehaerer/paste-reference/context-menu-actions
 */
class ContextMenuActions {
  /**
   * Paste record as a reference
   *
   * @param {String} table
   * @param {Number} uid of the record after which record from the clipboard will be pasted
   * @param {DOMStringMap} dataset
   */
  pasteReference(table, uid, dataset) {
    const performPaste = async () => {
      const result = await pasteAsReference(parseInt(dataset.clipboardUid, 10), dataset.clipboardTitle ?? '', {
        target: parseInt(dataset.target, 10),
        colPos: parseInt(dataset.colPos, 10),
        language: parseInt(dataset.language, 10),
        containerParent: parseInt(dataset.containerParent ?? '0', 10),
      }, parseInt(dataset.clipboardLanguage, 10));
      if (!result.hasErrors) {
        Viewport.ContentContainer.refresh();
      }
    };

    if (!dataset.title) {
      performPaste();
      return;
    }

    Modal.confirm(dataset.title, dataset.message, Severity.warning, [
      {
        text: dataset.buttonCloseText,
        active: true,
        btnClass: 'btn-default',
        name: 'cancel',
        trigger: (event, modal) => modal.hideModal(),
      },
      {
        text: dataset.buttonOkText,
        btnClass: 'btn-warning',
        name: 'ok',
        trigger: (event, modal) => {
          modal.hideModal();
          performPaste();
        },
      },
    ]);
  }
}

export default new ContextMenuActions();
