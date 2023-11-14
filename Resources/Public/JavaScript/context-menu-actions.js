/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
import $ from 'jquery';
import Modal from '@typo3/backend/modal.js';
import Severity from '@typo3/backend/severity.js';

/**
 * JavaScript to handle PasteReference related actions for Contextmenu
 * @exports @haerer/paste-reference/context-menu-actions
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
   */
  pasteReference (table, uid) {
    const performPaste = (element) => {
      const actionUrl = element.data('action-url');
      const url = actionUrl + '&redirect=' + ContextMenuActions.getReturnUrl();

      top.TYPO3.Backend.ContentContainer.setUrl(url);
    }

    const $anchorElement = $(this);
    if (!$anchorElement.data('title')) {
      performPaste($anchorElement);
      return;
    }
    Modal.confirm(
      $anchorElement.data('title'),
      $anchorElement.data('message'),
      Severity.warning, [
        {
          text: $(this).data('button-close-text') || TYPO3.lang['button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: function(event, modal) {
            modal.hideModal();
          }
        },
        {
          text: $(this).data('button-ok-text') || TYPO3.lang['button.ok'] || 'OK',
          btnClass: 'btn-warning',
          name: 'ok',
          trigger: function(event, modal) {
            modal.hideModal();
            performPaste($anchorElement);
          }
        }
      ]);
  };
}
export default new ContextMenuActions();
