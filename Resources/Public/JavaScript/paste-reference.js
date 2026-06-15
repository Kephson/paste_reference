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
import DataHandler from '@typo3/backend/ajax-data-handler.js';
import DocumentService from '@typo3/core/document-service.js';
import '@typo3/backend/element/icon-element.js';
import { MessageUtility } from "@typo3/backend/utility/message-utility.js";
import { default as Modal, ModalElement } from '@typo3/backend/modal.js';
import RegularEvent from '@typo3/core/event/regular-event.js';

import DragDrop from "@ehaerer/paste-reference/paste-reference-drag-drop.js";
import Draggable from "@ehaerer/paste-reference/paste-reference-draggable.js";

class PasteReference {

  /**
   * initializes paste icons for all content elements on the page
   */
  constructor(args) {
    // assuring singleton
    if (PasteReference.instance) {
      return PasteReference.instance;
    }
    PasteReference.instance = this;

    this.itemOnClipboardUid = 0;
    this.itemOnClipboardTitle = '';
    this.copyMode = '';
    this.elementIdentifier = '.t3js-page-ce';
    this.pasteAfterLinkTemplate = '';
    this.pasteIntoLinkTemplate = '';
    this.copyFromAnotherPageLinkTemplate = '';
    this.itemOnClipboardUid = args.itemOnClipboardUid;
    this.itemOnClipboardTitle = args.itemOnClipboardTitle;
    this.copyMode = args.copyMode;
    DocumentService.ready().then(() => {
      if (document.querySelectorAll('.t3js-page-columns').length > 0) {
        // promise to assure that most recent data are used
        this.getClipboardData()
          .then(() => {
            this.generateButtonTemplates();
            this.activatePasteIcons();
            this.initializeEvents();
            this.initModalEventListener();
          })
          .catch((err) => {
            console.error('Error initializing PasteReference:', err);
          });
      }
    });
  }

  getClipboardData() {
    return (new AjaxRequest(top.TYPO3.settings.Clipboard.moduleUrl))
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
        else {
          console.error('Error: ClipboardData couldn\'t be retieved.');
        };
      });
  }

  initializeEvents() {
    if (this.itemOnClipboardUid) {
      this.waitForElm(document, '.t3js-paste-pr').then(() => {
        new RegularEvent('click', (evt, target) => {
          evt.preventDefault();
          this.activatePasteModal(evt, target);
        }).delegateTo(document, '.t3js-paste-pr');
      });
    }
    this.waitForElm(document, '.t3js-paste-new').then(() => {
      new RegularEvent('click', (evt, target) => {
        evt.preventDefault();
        this.copyFromAnotherPage(target);
      }).delegateTo(document, '.t3js-paste-new');
    });
    this.waitForElm(document, '[data-contextmenu-id="root_copyRelease"]').then(() => {
      new RegularEvent('click', (evt, target) => {
        this.itemOnClipboardUid = undefined;
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
      // If you get "parameter 1 is not of type 'Node'" error,
      // see https://stackoverflow.com/a/77855838/492336
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
    if (!document.querySelectorAll('.typo3-TCEforms').length) {
      window.addEventListener('message', (event) => {
        if (!MessageUtility.verifyOrigin(event.origin)) {
          throw 'Denied message sent by ' + event.origin;
        }
        if (event.data.actionName === 'typo3:elementBrowser:elementAdded') {
          if (typeof event.data.fieldName === 'undefined') {
            throw 'fieldName not defined in message';
          }
          if (typeof event.data.value === 'undefined') {
            throw 'value not defined in message';
          }

          const elementId = event.data.value;
          const regex_1 = /(page_(\d+)-)(txContainerParent_(\d+)-)(colpos_(\d+)-)tt_content[\_\-](\d+)/;
          const regex_2 = /(page_(\d+)-)(colpos_(\d+)-)tt_content[\_\-](\d+)/;
          const regex_3 = /tt_content[\_\-](\d+)/;
          const targetId = event.data.fieldName;
          let page = 0;
          let txContainerParent = 0;
          let colpos = 0;
          let tableUid = 0;
          let targetUid = 0;

          if (targetId.indexOf('txContainerParent') >= 0) {
            const result = targetId.match(regex_1);
            if (result) {
              page = result[2];
              txContainerParent = result[4];
              colpos = result[6];
              targetUid = result[7];
            }
          }
          else if (targetId.indexOf('colpos') >= 0) {
            const result = targetId.match(regex_2);
            if (result) {
              page = result[2];
              colpos = result[4];
              targetUid = result[5];
            }
          }
          if (elementId.indexOf('colpos') >= 0) {
            const regex = /(colpos_(\d+)-)tt_content_(\d+)/;
            const result = elementId.match(regex);
            if (result) {
              // colpos = result[2];
              tableUid = result[3];
            }
          } else {
            const regex = /tt_content_(\d+)/;
            tableUid = elementId.match(regex, 'tt_content_', '')[1] * 1;
          }

          // Create Draggable object for the selected element
          const draggableElement = document.querySelector('#' + elementId);
          const draggableObj = new Draggable();
          try {
            if (draggableElement) {
              // draggableObj.init(draggableElement);
            } else {
              // Set basic properties if element not found in DOM
              draggableObj
                .setPid(page)
                .setColpos(colpos)
                .setUid(tableUid);

              if (txContainerParent > 0) {
                draggableObj.setTxContainerParent(txContainerParent);
              }
              if (targetUid === 0) {
                draggableObj.setSorting(0);
              }

              // Extract page ID from current page context
              const pageElement = document.querySelector('[data-page]');
              if (pageElement && pageElement.dataset.page) {
                draggableObj.setPid(parseInt(pageElement.dataset.page, 10));
              }

            }
          } catch (error) {
            console.error('Error creating draggable object from modal selection:', error);
            return;
          }

          const droppableElement = document.querySelector('#' + targetId + ' .t3js-paste-new');

          DragDrop.default.onDrop(
            draggableObj,
            droppableElement,
            this.itemOnClipboardUid,
            event,
            'copyFromAnotherPage'
          );
        }
      })
    }
  }

  generateButtonTemplates() {
    this.copyFromAnotherPageLinkTemplate = '<button type="button"'
      + ' class="t3js-paste-new btn btn-default btn-sm"'
      + ' title="' + TYPO3.lang['tx_paste_reference_js.copyfrompage'] + '">'
      + '<typo3-backend-icon identifier="actions-insert-reference" size="small"></typo3-backend-icon>'
      + '</button>';
    /*
    // might removal fix issue #79?
    if (!this.itemOnClipboardUid) {
      return;
    }
    */
    this.pasteAfterLinkTemplate = '<button'
      + ' type="button"'
      + ' class="t3js-paste t3js-paste' + (this.copyMode ? '-' + this.copyMode : '') + ' t3js-paste-after btn btn-default btn-sm"'
      + ' title="' + TYPO3.lang?.pasteAfterRecord + '">'
      + '<typo3-backend-icon identifier="actions-document-paste-into" size="small"></typo3-backend-icon>'
      + '</button>';
    this.pasteIntoLinkTemplate = '<button'
      + ' type="button"'
      + ' class="t3js-paste t3js-paste' + (this.copyMode ? '-' + this.copyMode : '') + ' t3js-paste-into btn btn-default btn-sm"'
      + ' title="' + TYPO3.lang?.pasteIntoColumn + '">'
      + '<typo3-backend-icon identifier="actions-document-paste-into" size="small"></typo3-backend-icon>'
      + '</button>';
  }

  /**
   * activates the paste into / paste after icons outside the context menus
   */
  activatePasteIcons() {
    const pid = document.querySelector('[data-page]').dataset.page;
    const allColumns = document.querySelectorAll('.t3js-page-column');

    allColumns.forEach((colElement) => {

      const currentColpos = colElement.dataset['colpos'];
      const allElements = colElement.querySelectorAll('.t3js-page-new-ce');

      allElements.forEach((element, index) => {

        const languageUid = parseInt(element.closest('[data-language-uid]').dataset.languageUid, 10);
        // const copyFromAnotherPageLink = document.createRange().createContextualFragment(this.copyFromAnotherPageLinkTemplate);
        const txContainerParentId = PasteReference.findTxContainerParentForElement(element) ?? 0;

        // if any item is in the clipboard
        if (this.itemOnClipboardUid > 0) {
          // waiting till default paste-buttons are created
          this.waitForElm(element, '.t3js-paste')
            .then((pasteButton) => {
              // 1) remove class from the default button and consequentially the click-EventHandler
              // 2) add class to the default button to attach an own click-EventHandler
              pasteButton.classList.replace('t3js-paste', 't3js-paste-pr');
              return pasteButton;
            })
            .then((pasteButton) => {
              const copyFromAnotherPageLink = document.createRange().createContextualFragment(this.copyFromAnotherPageLinkTemplate);
              // Fix button placement logic to prevent duplicates in container elements
              if (copyFromAnotherPageLink && element.querySelectorAll('.t3js-paste-new').length < 1) {
                // add additional button
                pasteButton.after(copyFromAnotherPageLink);
              }
            });
        } else if (element.querySelectorAll('.t3js-paste-new').length < 1) {
          const copyFromAnotherPageLink = document.createRange().createContextualFragment(this.copyFromAnotherPageLinkTemplate);
          if (copyFromAnotherPageLink) {
            // add additional button without waiting for default button
            element.append(copyFromAnotherPageLink);
          }
        }

        const colpos = currentColpos ?? PasteReference.determineColumn(element);
        const draggableObj = new Draggable();
        draggableObj
          .setFromElement(element)
          .setColpos(colpos)
          .setPid(pid)
          .setLanguageUid(languageUid);

        if (txContainerParentId) {
          draggableObj.setTxContainerParent(txContainerParentId);
        }

        let className = '';
        let setDraggableUid = false;

        // - assigning clear parameters for draggableObj,
        //   to get uniqueId, which is required for modal
        // - assigning semantic ClassNames which could be used later,
        //   to identify functional aspects. ClassNames are
        //   prefixed with `pr-`
        if (index === 0 && !element.parentElement.id) {
          draggableObj
            .setUid(0)
            .setSorting(0);

          if (txContainerParentId) {
            className = 'pr-container-column-top-bar';
          } else {
            className = 'pr-page-column-top-bar';
          }
          element.parentElement.classList.add('pr-column-top-bar');
          element.parentElement.classList.add(className);


        } else if (txContainerParentId) {

          // Set draggable.uid only when a content element exists,
          // this is not the case in column-top-bars.
          if (element.parentElement.parentElement.firstChild.classList.contains('t3-page-column-header')) {

            element.parentElement.classList.add('pr-container-column-top-bar');

          } else {

            element.parentElement.classList.add('pr-container-inner-ce');
            setDraggableUid = true;

          }

        } else if (index > 0 && element.parentElement.id) {

          element.parentElement.classList.add('pr-page-column-ce');
          setDraggableUid = true;

        }

        if (setDraggableUid) {
          const elementUid = element.closest('[data-uid]')?.dataset?.uid;
          if (elementUid) {
            draggableObj.setUid(parseInt(elementUid, 10));
          }
        }

        const uniqueId = draggableObj.getUniqueId();
        element.parentElement.setAttribute('id', uniqueId);

      })
    });
  }

  /**
   * generates the paste into / paste after modal
   */
  activatePasteModal(evt, element) {
    const draggable = new Draggable(element);
    const elementTitle = this.itemOnClipboardTitle !== undefined
      ? this.itemOnClipboardTitle
      : "[" + TYPO3.lang['tx_paste_reference_js.modal.labels.no_title'] + "]";
    const title = (TYPO3.lang['paste.modal.title.paste'] || 'Paste record') + ': "' + elementTitle + '"';
    const colpos = PasteReference.findColumnForElement(evt.target);
    const txContainerParent = draggable.getTxContainerParent();
    let url = null;
    let content = '';
    let buttons = [];
    let severity = top.TYPO3.Severity.info;
    draggable
      .setTxContainerParent(txContainerParent)
      .setColpos(colpos);

    if (element.dataset) {
      if (element.dataset.url) {
        url = element.dataset.url;
      }
      if (element.dataset.severity && (typeof top.TYPO3.Severity[element.dataset.severity] !== 'undefined')) {
        severity = top.TYPO3.Severity[element.dataset.severity];
      }
    }

    if (this.itemOnClipboardUid) {
      draggable.setUid(this.itemOnClipboardUid);
      if (element.classList.contains('t3js-paste-copy')) {

        content = (TYPO3.lang['tx_paste_reference_js.modal.pastecopy'] || 'How do you want to paste that clipboard content here?'); // + contextInfo;
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
            trigger: (evt, modal) => {
              modal.hideModal();
              // console.log('using execute');
              this.execute(element);
              // DragDrop.default.onDrop(draggable, element, this.itemOnClipboardUid, evt, 'copy');
            }
          },
          {
            text: TYPO3.lang['tx_paste_reference_js.modal.button.pastereference'] || 'Paste as reference',
            btnClass: 'text-white btn-' + top.TYPO3.Severity.getCssClass(severity),
            trigger: (evt, modal) => {
              modal.hideModal();
              DragDrop.default.onDrop(draggable, element, this.itemOnClipboardUid, evt, 'reference');
            }
          }
        ];
        if (top.pasteReferenceAllowed * 1 !== 1) {
          buttons.pop();
        }

      } else if (element.classList.contains('t3js-paste-after') || element.classList.contains('t3js-paste-into')) {

        content = (TYPO3.lang['paste.modal.paste'] || 'Do you want to move the record to this position?'); //  + contextInfo
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
            trigger: (evt, modal) => {
              modal.hideModal();
              DragDrop.default.onDrop(draggable, element, this.itemOnClipboardUid, evt, 'move');
            }
          }
        ];
      }
    }

    if (url !== null) {
      const separator = (url.indexOf('?') > -1) ? '&' : '?';
      const params = PasteReference.concatUrlData(element.dataset);
      // Load URL with AJAX, append the content to the modal-body and trigger the callback
      Modal.loadUrl(title, severity, buttons, url + separator + params);
    } else {
      // Shows a dialog
      Modal.show(title, content, severity, buttons);
    }
  }

  /**
   * Send an AJAX request via the AjaxDataHandler
   */
  execute(element) {
    const colPos = PasteReference.findColumnForElement(element);
    const txContainerParent = PasteReference.findTxContainerParentForElement(element);
    const closestElement = element.closest(this.elementIdentifier);
    const targetFound = closestElement.dataset.uid;
    let targetPid;
    if (typeof targetFound === 'undefined') {
      targetPid = parseInt(closestElement.dataset.page, 10);
    }
    else {
      targetPid = 0 - parseInt(targetFound, 10);
    }
    const language = parseInt(element.closest('[data-language-uid]').dataset.languageUid, 10);
    let parameters = {
      CB: {
        paste: 'tt_content|' + targetPid,
        pad: 'normal',
        update: {
          colPos: colPos,
          sys_language_uid: language,
          tx_container_parent: txContainerParent,
        },
      },
    };
    if (txContainerParent && typeof targetFound === 'undefined') {
      parameters.CB.update['sorting'] = '0';
    }
    DataHandler.process(parameters).then((result) => {
      if (!result.hasErrors) {
        window.location.reload();
      }
    });
  }

  static concatUrlData(dataset) {
    let params = '';
    let n = 0;
    for (const property in dataset) {
      params += (n > 0 ? '&' : '') + encodeURIComponent(property) + '=' + encodeURIComponent(dataset[property]);
      n++;
    }
    return params;
  }

  /**
   * returns the next "upper" container parent parameter inside the code
   * @param element
   * @return int|boolean the containerParent
   */
  static findTxContainerParentForElement(element) {
    let txContainerParent = false;
    const gridContainer = element && element.closest('[data-tx-container-parent]')
      ? element.closest('[data-tx-container-parent]')
      : [];
    if (gridContainer.dataset && gridContainer.dataset['txContainerParent'] !== 'undefined') {
      txContainerParent = gridContainer.dataset['txContainerParent'];
    }
    return txContainerParent;
  }

  static findColumnForElement(item) {
    let element = item;
    // if 'item' is an event, the element has to be fetched out of it
    if (item.type && item.type == 'click') {
      element = item.srcElement;
    }
    // is element td.t3js-page-column for first button in the column
    // with distinct class to determine the column (for example like t3-grid-cell-left_col)
    // else it's a div.t3-page-ce-wrapper
    const columnContainer = element.closest('[data-colpos]');
    const column = parseInt(columnContainer?.dataset?.colpos ?? '0', 10);
    return column;
  }
}

export default PasteReference;

if (!PasteReference.instance && top.pasteReferenceAllowed) {
  const pollTime = 100;
  window.setTimeout(function() {
    if (!PasteReference.instance) {
      const pasteReference = new PasteReference({});
    }
  }, pollTime);
}
