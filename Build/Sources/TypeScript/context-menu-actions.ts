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

import { default as Modal, ModalElement } from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';
import Helper from '@typo3/paste-reference/helper';

/**
 * @exports @ehaerer/paste-reference/context-menu-actions-extended
 */
class ContextMenuActions {

  /**
   * @returns {String}
   */
  static getReturnUrl(): string {
    return encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
  }

  /**
   * Paste record as a reference
   *
   * @param {String} table
   * @param {Number} uid of the record after which record from the clipboard will be pasted
   * @param {DOMStringMap} dataset The data attributes of the invoked menu item
   */
  public static pasteReference(table: string, uid: number, dataset: DOMStringMap): void {
    const performPaste = (givenDataset: DOMStringMap): void => {
      const actionUrl = givenDataset.actionUrl;
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
          trigger: function (event: Event, modal: ModalElement): void {
            modal.hideModal();
          }
        },
        {
          text: dataset.buttonOkText || TYPO3.lang['button.ok'] || 'OK',
          btnClass: 'btn-warning',
          name: 'ok',
          trigger: function (event: Event, modal: ModalElement): void {
            modal.hideModal();
            performPaste(dataset);
          }
        }
      ]
    );
  }
}

export default ContextMenuActions;
