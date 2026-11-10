import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import Modal from '@typo3/backend/modal.js';
import { MessageUtility } from '@typo3/backend/utility/message-utility.js';

const TABLE_PREFIX = 'tt_content_';
let tokenCounter = 0;

/**
 * @param {number} uid
 * @returns {Promise<{uid: number, CType: string, sys_language_uid: number, title: string}|null>}
 *   null when the record is unknown or not accessible
 */
export async function fetchRecordInfo(uid) {
  try {
    const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.paste_reference_record_info)
      .withQueryArguments({ uid })
      .get();
    return await response.resolve();
  } catch {
    return null;
  }
}

/**
 * @param {string} cType CType of the record to paste
 * @param {boolean} referenceAllowed whether the user may create shortcut elements
 * @param {function(string): boolean} isAllowedInTarget column restriction of the target
 * @returns {{copy: boolean, reference: boolean}}
 */
export function getPasteActions(cType, referenceAllowed, isAllowedInTarget) {
  return {
    copy: isAllowedInTarget(cType),
    reference: referenceAllowed && isAllowedInTarget('shortcut'),
  };
}

/**
 * Opens the element browser and resolves with the selected tt_content record,
 * or with null when the browser was closed without a selection.
 *
 * The result is delivered after the browser modal is hidden, as the element
 * browser dismisses all open modals when it closes itself.
 *
 * @param {string} elementBrowserUrl
 * @returns {Promise<{uid: number, title: string}|null>}
 */
export function pickRecord(elementBrowserUrl) {
  return new Promise((resolve) => {
    const token = 'paste-reference-' + (++tokenCounter);
    const url = new URL(elementBrowserUrl, window.location.origin);
    url.searchParams.set('bparams', token + '|||tt_content|');

    let selection = null;
    const modal = Modal.advanced({
      type: Modal.types.iframe,
      content: url.toString(),
      size: Modal.sizes.large,
    });

    const onMessage = (event) => {
      if (!MessageUtility.verifyOrigin(event.origin)) {
        return;
      }
      const message = event.data;
      if (message?.actionName !== 'typo3:elementBrowser:elementAdded' || message.fieldName !== token) {
        return;
      }
      if (typeof message.value !== 'string' || !message.value.startsWith(TABLE_PREFIX)) {
        return;
      }
      selection = {
        uid: parseInt(message.value.substring(TABLE_PREFIX.length), 10),
        title: message.label ?? '',
      };
      modal.hideModal();
    };

    window.addEventListener('message', onMessage);
    modal.addEventListener('typo3-modal-hidden', () => {
      window.removeEventListener('message', onMessage);
      resolve(selection);
    }, { once: true });
  });
}
