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

import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import DocumentService from '@typo3/core/document-service.js';
import { default as Modal } from "@typo3/backend/modal.js";
import Paste from "@typo3/backend/layout-module/paste.js";
import DragDrop from "@ehaerer/paste-reference/paste-reference-drag-drop.js";
import { MessageUtility } from "@typo3/backend/utility/message-utility.js";
import RegularEvent from '@typo3/core/event/regular-event.js';

class OnReady {
  openedPopupWindow = [];

  /**
   * generates the paste into / paste after modal
   */
  copyFromAnotherPage(element) {
    let idString = '';
    if (element.offsetParent && element.offsetParent.id) {
      idString = element.offsetParent.id
    }
    const url = top.browserUrl + '&mode=db&bparams=' + idString + '|||tt_content|';
    const configurationIframe = {
      type: Modal.types.iframe,
      content: url,
      size: Modal.sizes.large
    };
    Modal.advanced(configurationIframe);
  };

  getClipboardData() {
    (new AjaxRequest(top.TYPO3.settings.Clipboard.moduleUrl))
    .withQueryArguments({ action: 'getClipboardData' })
    .post({ table: 'tt_content' })
    .then(async (response) => {
      const resolvedBody = await response.resolve();
      if (resolvedBody.success === true) {
        let data = resolvedBody.data;
        let record = data ? resolvedBody.data.tabs[0].items[0] : [];
        let identifier = record ? record.identifier : '';
        let table = identifier ? identifier.split('|')[0] : '';
        let uid = identifier ? identifier.split('|')[1] : 0;
        let title = record ? record.title.replace(/<[^>]*>?/gm, '') : '';
        let clipboardData = {
          copyMode: resolvedBody.data.copyMode,
          data: record,
          itemOnClipboardUid: uid * 1,
          itemOnClipboardTitleHtml: record ? record.title : '',
          itemOnClipboardTitle: title,
          itemOnClipboardTable: table,
        };
        top.itemOnClipboardUid = clipboardData.itemOnClipboardUid;
        top.itemOnClipboardTitle = clipboardData.itemOnClipboardTitle;
        top.itemOnClipboardTitleHtml = clipboardData.itemOnClipboardTitleHtml;
        top.itemOnClipboardTable = clipboardData.itemOnClipboardTable;
        return clipboardData;
      }
      else return {
        copyMode: '',
        data: {},
        itemOnClipboardUid: 0,
        itemOnClipboardTitleHtml: '',
        itemOnClipboardTitle: '',
        itemOnClipboardTable: '',
      };
    });
  }

  waitForElm(elementAbove, selector) {
    return new Promise(resolve => {
      if (elementAbove.querySelector(selector)) {
        return resolve(elementAbove.querySelector(selector));
      }
      const observer = new MutationObserver(mutations => {
        if (elementAbove.querySelector(selector)) {
          observer.disconnect();
          resolve(elementAbove.querySelector(selector));
        }
      });
      // If you get "parameter 1 is not of type 'Node'" error, see https://stackoverflow.com/a/77855838/492336
      observer.observe(document.body, {
        childList: true,
        subtree: true
      });
    });
  }
}
const onReady = new OnReady;

/**
 * generates the paste into / paste after modal
 */
Paste.activatePasteModal = function(element) {
  const url = element.dataset.url || null;
  const elementTitle = this.itemOnClipboardTitle != undefined ? this.itemOnClipboardTitle : "["+TYPO3.lang['tx_paste_reference_js.modal.labels.no_title']+"]";
  const title = (TYPO3.lang['paste.modal.title.paste'] || 'Paste record') + ': "' + elementTitle + '"';
  const severity = (typeof top.TYPO3.Severity[element.dataset.severity] !== 'undefined') ? top.TYPO3.Severity[element.dataset.severity] : top.TYPO3.Severity.info;
  let buttons = [];
  let content = '';

  if (element.classList.contains('t3js-paste-copy')) {
    content = TYPO3.lang['tx_paste_reference_js.modal.pastecopy'] || 'How do you want to paste that clipboard content here?';
    buttons = [
      {
        text: TYPO3.lang['paste.modal.button.cancel'] || 'Cancel',
        active: true,
        btnClass: 'btn-default',
        trigger: (evt, modal) => modal.hideModal(),
      },
      {
        text: TYPO3.lang['tx_paste_reference_js.modal.button.pastecopy'] || 'Paste as copy',
        btnClass: 'text-white btn-' + top.TYPO3.Severity.getCssClass(severity),
        trigger: function(evt, modal) {
          modal.hideModal();
          DragDrop.default.onDrop(top.itemOnClipboardUid, element, evt);
        }
      },
      {
        text: TYPO3.lang['tx_paste_reference_js.modal.button.pastereference'] || 'Paste as reference',
        btnClass: 'text-white btn-' + top.TYPO3.Severity.getCssClass(severity),
        trigger: function(evt, modal) {
          modal.hideModal();
          DragDrop.default.onDrop(top.itemOnClipboardUid, element, evt, 'reference');
        }
      }
    ];
    if (top.pasteReferenceAllowed * 1 !== 1) {
      buttons.pop();
    }
  } else {
    content = TYPO3.lang['paste.modal.paste'] || 'Do you want to move the record to this position?';
    buttons = [
      {
        text: TYPO3.lang['paste.modal.button.cancel'] || 'Cancel',
        active: true,
        btnClass: 'btn-default',
        trigger: (evt, modal) => modal.hideModal(),
      },
      {
        text: TYPO3.lang['paste.modal.button.paste'] || 'Move',
        btnClass: 'btn-' + Severity.getCssClass(severity),
        trigger: function(evt, modal) {
          modal.hideModal();
          DragDrop.default.onDrop(top.itemOnClipboardUid, element, null);
        }
      }
    ];
  }
  if (url !== null) {
    const separator = (url.indexOf('?') > -1) ? '&' : '?';
    const params = $.param({data: $element.data()});
    Modal.loadUrl(title, severity, buttons, url + separator + params);
  } else {
    Modal.show(title, content, severity, buttons);
  }
};

/**
 * activates the paste into / paste after and fetch copy from another page icons outside of the context menus
 */
Paste.activatePasteIcons = function() {
  if (top.copyFromAnotherPageLinkTemplate) {
    const allElements = document.querySelectorAll('.t3js-page-new-ce');
    allElements.forEach((element, index) => {
      if (element.querySelector('.icon-actions-plus')) {
        const copyFromAnotherPageLink = document.createRange().createContextualFragment(top.copyFromAnotherPageLinkTemplate);

        // if any item is in the clipboard
        if (top.itemOnClipboardUid > 0) {

          // waiting till default paste-buttons are created
          onReady.waitForElm(element, '.t3js-paste').then((pasteButton) => {
              // add additional button
              pasteButton.after(copyFromAnotherPageLink);

              // 1) remove class from the default button and consequentally the click-EventHandler
              // 2) add class to the default button to attach an own click-EventHandler
              pasteButton.classList.replace('t3js-paste', 't3js-paste-default');
          });
        } else {
            // add additional button without waiting for default button
            // as that one won't be shown with empty clipboard
            element.append(copyFromAnotherPageLink);
        }
        // Assigning id to first button-bar as it's missing because
        // it's not connected to a distinct content-element but required for modal
        if (index === 0 && !element.parentElement.id) {
          let tmpId = allElements[index + 1].parentElement.id;
          let regex = /tt_content-[0-9]+/;
          let id = tmpId.replace(regex, 'tt_content-0');
          element.parentElement.setAttribute('id', id);
        }
      };
    });
  };
};

Paste.initializeEvents = function() {
  if (top.itemOnClipboardUid > 0) {
    onReady.waitForElm(document, '.t3js-paste-default').then(() => {
      new RegularEvent('click', (evt, target) => {
        evt.preventDefault();
        this.activatePasteModal(target);
      }).delegateTo(document, '.t3js-paste-default');
    });
  }
  onReady.waitForElm(document, '.t3js-paste-new').then(() => {
    new RegularEvent('click', (evt, target) => {
      evt.preventDefault();
      onReady.copyFromAnotherPage(target);
    }).delegateTo(document, '.t3js-paste-new');
  });
};

/**
 * gives back the data from the popup window with record-selection to the copy action
 *
 * $('.typo3-TCEforms') is not relevant here as it exists on
 * detail pages for single records only.
 */
if (!document.querySelector('.typo3-TCEforms')) {
  window.addEventListener('message', function(evt) {

    if (!MessageUtility.verifyOrigin(evt.origin)) {
      throw 'Denied message sent by ' + evt.origin;
    }

    if (typeof evt.data.fieldName === 'undefined') {
      throw 'fieldName not defined in message';
    }

    if (typeof evt.data.value === 'undefined') {
      throw 'value not defined in message';
    }

    const result = evt.data.value;
    const tableUid = result.replace('tt_content_', '') * 1;
    const elementId = evt.data.fieldName;
    DragDrop.default.onDrop(
      tableUid,
      document.querySelector('#' + elementId).querySelector('.t3js-paste-new'),
      'copyFromAnotherPage'
    );
  });
}

DocumentService.ready().then(() => {
  onReady.getClipboardData();
  Paste.activatePasteIcons();
  Paste.initializeEvents();
});

export default OnReady;
