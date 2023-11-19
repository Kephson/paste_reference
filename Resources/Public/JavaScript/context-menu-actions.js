import Modal from '@typo3/backend/modal.js';
import Severity from '@typo3/backend/severity.js';
import Helper from '@ehaerer/paste-reference/helper.js';

/**
 * JavaScript to handle PasteReference related actions for Contextmenu
 * @exports @ehaerer/paste-reference/context-menu-actions
 */
class ContextMenuActions {
  /**
   * @returns {String}
   */
  static getReturnUrl() {
    return encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
  }

  /**
   * Paste record as a reference
   *
   * @param {String} table
   * @param {Number} uid of the record after which record from the clipboard will be pasted
   * @param dataset
   */
  pasteReference(table, uid, dataset) {
    const performPaste = (dataset) => {
      const actionUrl = dataset.actionUrl;
      const url = actionUrl + '&redirect=' + ContextMenuActions.getReturnUrl();
      top.TYPO3.Backend.ContentContainer.setUrl(url);
    }

    if (!dataset.title) {
      performPaste(dataset);
      return;
    }

    Modal.confirm(
      dataset.title,
      Helper.decodeHtmlspecialChars(dataset.message),
      Severity.warning, [
        {
          text: dataset.buttonCloseText || TYPO3.lang['button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: function (event, modal) {
            modal.hideModal();
          }
        },
        {
          text: dataset.buttonOkText || TYPO3.lang['button.ok'] || 'OK',
          btnClass: 'btn-warning',
          name: 'ok',
          trigger: function (event, modal) {
            modal.hideModal();
            performPaste(dataset);
          }
        }
      ]);

  };
}

export default new ContextMenuActions();
