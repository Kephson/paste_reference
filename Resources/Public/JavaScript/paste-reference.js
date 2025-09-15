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
// import Paste from "@typo3/backend/layout-module/paste.js";
import DragDrop from "@ehaerer/paste-reference/paste-reference-drag-drop.js";
import { MessageUtility } from "@typo3/backend/utility/message-utility.js";
import RegularEvent from '@typo3/core/event/regular-event.js';


/**
 * Disable default functions
Paste.generateButtonTemplates = function() {}
Paste.activatePasteIcons = function() {}
Paste.initializeEvents = function() {}
 */

class PasteReference {

  static instanceCount = 0;

  /**
   * initializes paste icons for all content elements on the page
   */
  constructor(args) {
    PasteReference.instanceCount++;
    this.itemOnClipboardUid = 0;
    this.itemOnClipboardTitle = '';
    this.copyMode = '';
    this.elementIdentifier = '.t3js-page-ce';
    this.pasteAfterLinkTemplate = '';
    this.pasteIntoLinkTemplate = '';
    this.copyFromAnotherPageLinkTemplate = '';
    this.itemOnClipboardUid = args.itemOnClipboardUid;
    this.itemOnClipboardTitle = args.itemOnClipboardTitle;
    this.itemOnClipboardTitleHtml = '';
    this.itemOnClipboardTable = '';
    this.copyMode = ''; //args.copyMode;
    DocumentService.ready().then(() => {
      if (document.querySelectorAll('.t3js-page-column').length > 0) {
        this.getClipboardData()
        this.generateButtonTemplates();
        this.activatePasteIcons();
        this.initializeEvents();
        this.initModalEventListener();
      }
    });
  }

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
        this.copyMode = resolvedBody.data.copyMode;
        this.itemOnClipboardUid = uid * 1;
        this.itemOnClipboardTitle = title;
        this.itemOnClipboardTitleHtml = record ? record.title : '';
        this.itemOnClipboardTable = table;
      }
    });
  }

  static determineColumn(element) {
    const columnContainer = element.closest('[data-colpos]');
// console.log(columnContainer);
    return parseInt(columnContainer?.dataset?.colpos ?? '0', 10);
  }

  static colIsEmpty(colpos) {
    let  columnWrapper = document.querySelector('t3js-page-column[data-colpos="' + colpos + '"] .t3-page-ce-wrapper');
    return columnWrapper.children.length > 0 ? false : true;
  }

  generateButtonTemplates() {
    this.copyFromAnotherPageLinkTemplate = '<button type="button"'
    + ' class="t3js-paste-new btn btn-default btn-sm"'
    + ' title="' + TYPO3.lang['tx_paste_reference_js.copyfrompage'] + '">'
    + '<typo3-backend-icon identifier="actions-insert-reference" size="small"></typo3-backend-icon>'
    + '</button>';
  }

  initializeEvents() {
    const thisClass = this;
    if (this.itemOnClipboardUid) {
      this.waitForElm(document, '.t3js-paste-default').then(() => {
        new RegularEvent('click', (evt, target) => {
          evt.preventDefault();
          thisClass.activatePasteModal(target);
        }).delegateTo(document, '.t3js-paste-default');
      });
    }
    this.waitForElm(document, '.t3js-paste-new').then(() => {
      new RegularEvent('click', (evt, target) => {
        evt.preventDefault();
        thisClass.copyFromAnotherPage(target);
      }).delegateTo(document, '.t3js-paste-new');
    });
    this.waitForElm(document, '[data-contextmenu-id="root_copyRelease"]').then(() => {
      new RegularEvent('click', (evt, target) => {
        thisClass.itemOnClipboardUid=undefined;
        console.log(thisClass.itemOnClipboardUid);
      }).delegateTo(document, '[data-contextmenu-id="root_copyRelease"]');
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

  /**
   * generates the paste into / paste after modal
   */
  copyFromAnotherPage(element) {
    let idString = '';
    if (element.offsetParent && element.offsetParent.id) {
      idString = element.offsetParent.id
    }
    const url = top.browserUrl + '&mode=db&bparams=' + idString + '|||tt_content|';
    // console.log(url);
    const configurationIframe = {
      type: Modal.types.iframe,
      content: url,
      size: Modal.sizes.large
    };
    Modal.advanced(configurationIframe);
  }

  /**
   * gives back the data from the popup window with record-selection to the copy action
   *
   * $('.typo3-TCEforms') is not relevant here as it exists on
   * detail pages for single records only.
   */
  initModalEventListener() {
    const thisClass = this;
    if (!document.querySelectorAll('.typo3-TCEforms').length) {
      window.addEventListener('message', function (event) {
        if (!MessageUtility.verifyOrigin(event.origin)) {
          throw 'Denied message sent by ' + event.origin;
        }
        if (event.data.actionName === 'typo3:elementBrowser:elementAdded') {
          if (typeof event.data.fieldName === 'undefined') {
            throw 'fieldName not defined in message';
            // console.log('fieldName not defined in message');
          }
          if (typeof event.data.value === 'undefined') {
            throw 'value not defined in message';
          }
// console.log({'event.data': event.data});
          const elementId = event.data.value;
          const targetId = event.data.fieldName;
          let regex = '';
          let tableUid = 0;
          let colpos = 0;
          let targetUid = 0;
          if (targetId.indexOf('colpos') >= 0) {
            regex = /(colpos_([0-9]+)-)tt_content[\_\-]([0-9]+)/;
            const result = targetId.match(regex);
// console.log(resultTarget);
            if (result) {
              colpos = result[2];
              targetUid = result[3];
            }
          }
          if (elementId.indexOf('colpos') >= 0) {
            regex = /(colpos_([0-9]+)-)tt_content[\_\-]([0-9]+)/;
            const result = elementId.match(regex);
            if (result) {
              // colpos = result[2];
              tableUid = result[3];
            }
          } else {
            regex = /tt_content[\_\-]([0-9]+)/;
            tableUid = elementId.match(regex, 'tt_content_', '')[1] * 1;
          }
          const draggableIdentifier = colpos + '|' + tableUid;
          // const element_1 = document.querySelector('#' + elementId);
          //const droppableElement = document.querySelector('#' + elementId + ' .t3js-paste-new');
          const droppableElement = document.querySelector('#' + targetId + ' .t3js-paste-new'); // + ' .t3js-page-ce-dropzone-available');
/*
console.log({
  'e.data.actionName': event.data.actionName,
  'elementId': elementId,
  'elementId.indexOf(colpos)': elementId.indexOf('colpos'),
  // 'identifier': identifier,
  'regex':regex,
  'tableUid':tableUid,
  'elementId.match(regex))': elementId.match(regex),
  'colpos': colpos,
  'targetId': targetId,
  'draggableIdentifier': draggableIdentifier,
  'droppableElement': droppableElement,
  // 'result':result,
});
*/
          DragDrop.default.onDrop(
            draggableIdentifier,
            droppableElement,
            thisClass.itemOnClipboardUid,
            event,
            'copyFromAnotherPage'
          );
        }
      })
    }
  }

  /**
   * activates the paste into / paste after and fetch copy from another page icons outside of the context menus
   */
  activatePasteIcons () {
    const thisClass = this;
    // thisClass.pasteReferenceIconCount = 0;
    if (this.copyFromAnotherPageLinkTemplate) {
      const allColumns = document.querySelectorAll('.t3js-page-column');
      allColumns.forEach((colElement) => {
        const currentColpos = colElement.dataset['colpos'];
        // console.log({'currentColpos': currentColpos, 'colElement': colElement});



          // console.log('copyFromAnotherPageLinkTemplate found');
          const allElements = colElement.querySelectorAll('.t3js-page-new-ce');
          allElements.forEach((element, index) => {
            // thisClass.pasteReferenceIconCount = 0;

              const copyFromAnotherPageLink = document.createRange().createContextualFragment(this.copyFromAnotherPageLinkTemplate);

              // if any item is in the clipboard
              if (this.itemOnClipboardUid > 0) {

                // waiting till default paste-buttons are created
                thisClass.waitForElm(element, '.t3js-paste')
                  .then((pasteButton) => {
                    // 1) remove class from the default button and consequentally the click-EventHandler
                    // 2) add class to the default button to attach an own click-EventHandler
                    pasteButton.classList.replace('t3js-paste', 't3js-paste-default');
                    return pasteButton;
                  })
                  .then((pasteButton) => {
                    // avoid that button is added twice in container elements
                    if (!element.querySelectorAll('.t3js-paste-new').length) {
                      // add additional button
                      pasteButton.after(copyFromAnotherPageLink);
                    }
                });
              } else {
                // add additional button without waiting for default button
                element.append(copyFromAnotherPageLink);
              }
              // Assigning id to first button-bar as it's missing because
              // it's not connected to a distinct content-element but required for modal
              if (index === 0 && !element.parentElement.id) {
                const colpos = PasteReference.determineColumn(element);
                let id, tmpId;
                if (allElements[index + 1]) {
                  tmpId = allElements[index + 1].parentElement.id;
                  let regex = /tt_content(_|\-[0-9]+)/;
                  id = tmpId.replace(regex, 'colpos_' + colpos + '-tt_content_0');
                  // id = tmpId.replace(regex, 'tt_content-0');
                }
                id = id ? id : 'colpos_' + colpos + '-tt_content_0';
// console.log({'tmpId': tmpId, 'id':id});
                // id = id ? id : 'tt_content-0';
                element.parentElement.setAttribute('id', id);
              }
            //}
          })




      });
    }
  }

  /**
   * generates the "paste into" / "paste after" modal
   */
  activatePasteModal (element) {
    const thisClass = this;
    let url = element.dataset.url || null;
    const elementTitle = this.itemOnClipboardTitle != undefined
      ? this.itemOnClipboardTitle
      : "["+TYPO3.lang['tx_paste_reference_js.modal.labels.no_title']+"]";
    const title = (TYPO3.lang['paste.modal.title.paste'] || 'Paste record') + ': "' + elementTitle + '"';
    const severity = (typeof top.TYPO3.Severity[element.dataset.severity] !== 'undefined')
      ? top.TYPO3.Severity[element.dataset.severity]
      : top.TYPO3.Severity.info;
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
            DragDrop.default.onDrop(thisClass.itemOnClipboardUid, element, thisClass.itemOnClipboardUid, evt);
          }
        },
        {
          text: TYPO3.lang['tx_paste_reference_js.modal.button.pastereference'] || 'Paste as reference',
          btnClass: 'text-white btn-' + top.TYPO3.Severity.getCssClass(severity),
          trigger: function(evt, modal) {
            modal.hideModal();
            DragDrop.default.onDrop(thisClass.itemOnClipboardUid, element, thisClass.itemOnClipboardUid, evt, 'reference');
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
          btnClass: 'btn-' + top.TYPO3.Severity.getCssClass(severity),
          trigger: function(evt, modal) {
            modal.hideModal();
            DragDrop.default.onDrop(thisClass.itemOnClipboardUid, element, thisClass.itemOnClipboardUid, null);
          }
        }
      ];
    }

    if (url !== null) {
      const separator = (url.indexOf('?') > -1) ? '&' : '?';
      const params = this.concatUrlData(element.dataset);
      Modal.loadUrl(title, severity, buttons, url + separator + params);
    } else {
      Modal.show(title, content, severity, buttons);
    }
  }

  concatUrlData(dataset) {
    let params = '';
    let n = 0;
    for (const property in dataset) {
      params += (n>0 ? '&' : '') + encodeURIComponent(property) + '=' + encodeURIComponent(dataset[property]);
      n++;
    }
    return params;
  }

}

export default PasteReference;

if (PasteReference.instanceCount === 0 && top.pasteReferenceAllowed) {
  const pollTime = 100;
  window.setTimeout(function() {
    if (PasteReference.instanceCount === 0) {
      const pasteReference = new PasteReference({});
    }
  }, pollTime);
}
