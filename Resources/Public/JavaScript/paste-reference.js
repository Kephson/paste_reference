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
import $ from "jquery";
import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import { default as Modal } from "@typo3/backend/modal.js";
import Paste from "@typo3/backend/layout-module/paste.js";
import DragDrop from "@ehaerer/paste-reference/paste-reference-drag-drop.js";
import { MessageUtility } from "@typo3/backend/utility/message-utility.js";

/**
 * Module: @ehaerer/paste-reference/paste-reference.js
 */

'use strict';

/**
 * @exports @ehaerer/paste-reference/paste-reference.js
 */
class PasteReference {

  static instanceCount = 0;

  // openedPopupWindow = [];

  constructor() {
    PasteReference.instanceCount++;
    this.activatePasteIcons();
    this.initModalEventListener();
  }

  /**
   * gets Clipboard data by Ajax and adds them
   * to the "top" variable
   */
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

        top.itemOnClipboardUid = uid * 1;
        top.itemOnClipboardTitle = title;
        top.itemOnClipboardTitleHtml = record ? record.title : '';
        top.itemOnClipboardTable = table;
      }
    });
  }

  /**
  * activates the icons for "paste into" / "paste after" and to
  * fetch a copy from another page outside of the context menus
  */
  activatePasteIcons() {
    const thisClass = this;
    this.getClipboardData();

    $('.t3js-page-new-ce').each(function () {
      const addButton = this;
      if (top.itemOnClipboardUid) {
        // sorting of the buttons is important, else the modal
        // for the first one is not working correctly,
        // therefore the buttons are added by promises
        $.when($(this).find('button.t3js-paste'))
        .then(() => {
            thisClass.alterDefaultPasteButton(this);
            // avoid that button is added twice in container elements
            // console.log(this, this.querySelectorAll('.t3js-paste-new').length);
            if (this.querySelectorAll('.t3js-paste-new').length < 1) {
              // add additional button
              thisClass.addPasteReferenceButton(addButton, $(this).find('button.t3js-paste-default'));
            }
          })
          .catch((error) => {console.error(error)});

      } else {
        thisClass.addPasteReferenceButton(addButton);
      }
    });
  }

  /**
  * generates the "paste into" / "paste after" modal
  */
  activatePasteModal(element) {
    const $element = $(element);
    const url = $element.data('url') || null;
    const elementTitle = top.itemOnClipboardTitle != undefined ? top.itemOnClipboardTitle : "["+TYPO3.lang['tx_paste_reference_js.modal.labels.no_title']+"]";
    const title = (TYPO3.lang['paste.modal.title.paste'] || 'Paste record') + ': "' + elementTitle + '"';
    const severity = (typeof top.TYPO3.Severity[$element.data('severity')] !== 'undefined') ? top.TYPO3.Severity[$element.data('severity')] : top.TYPO3.Severity.info;
    let buttons = [];
    let content = '';

    if ($element.hasClass('t3js-paste-copy')) {
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
          trigger: function (evt, modal) {
            modal.hideModal();
            DragDrop.default.onDrop(top.itemOnClipboardUid, $element, evt);
          }
        },
        {
          text: TYPO3.lang['tx_paste_reference_js.modal.button.pastereference'] || 'Paste as reference',
          btnClass: 'text-white btn-' + top.TYPO3.Severity.getCssClass(severity),
          trigger: function (evt, modal) {
            modal.hideModal();
            DragDrop.default.onDrop(top.itemOnClipboardUid, $element, evt, 'reference');
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
          trigger: function (evt, modal) {
            modal.hideModal();
            DragDrop.default.onDrop(top.itemOnClipboardUid, $element, null);
          }
        }
      ];
    }
    if (url !== null) {
      const separator = (url.indexOf('?') > -1) ? '&' : '?';
      const params = $.param({data: $element.data()});
      if (document.querySelectorAll('.typo3-backend-modal').length < 1) {
        Modal.loadUrl(title, severity, buttons, url + separator + params);
      }
    } else {
      if (document.querySelectorAll('.typo3-backend-modal').length < 1) {
        Modal.show(title, content, severity, buttons);
      }
    }
  }

  /**
   * gives back the data from the popup window with record-selection to the copy action
   *
   * $('.typo3-TCEforms') is not relevant here as it exists on
   * detail pages for single records only.
   */
  initModalEventListener() {
    if (!$('.typo3-TCEforms').length) {
      window.addEventListener('message', function (evt) {

        if (!MessageUtility.verifyOrigin(evt.origin)) {
          throw 'Denied message sent by ' + evt.origin;
        }
        if (typeof evt.data.fieldName === 'undefined') {
          // throw 'fieldName not defined in message';
          console.log('fieldName not defined in message');
        }
        if (typeof evt.data.value === 'undefined') {
          throw 'value not defined in message';
        }
        const result = evt.data.value;
        const tableUid = result.replace('tt_content_', '') * 1;
        const elementId = evt.data.fieldName;
        DragDrop.default.onDrop(tableUid, $('#' + elementId).find('.t3js-paste-new'), 'copyFromAnotherPage');
      });
    }
  }

  /**
   * is changing the common paste button to show
   * the modal window with extended options
   */
  alterDefaultPasteButton(button) {
    const thisClass = this;
    // replace class and in consequence the corresponding EventListener
    $(button).find('button.t3js-paste').addClass('t3js-paste-default').removeClass('t3js-paste');

    // add custom click-EventListener on default paste button
    $(button).on('click', '.t3js-paste-default', (evt) => {
      evt.preventDefault();
      thisClass.activatePasteModal($(evt.currentTarget));
    });
  }

  addPasteReferenceButton(addButton, $pasteButton = null) {
    const thisClass = this;
    const $pasteReferenceButton = $(top.copyFromAnotherPageLinkTemplate);

    if ($pasteButton) {
      // promise to add custom button and eventHandler
      // if 'button.t3js-paste' is found
      $.when($pasteButton.after($pasteReferenceButton))
        .then(
          thisClass.initClickEventListener($pasteReferenceButton)
        )
        .catch((error) => {console.error(error)});
    } else {
      $(addButton).append($pasteReferenceButton);
      thisClass.initClickEventListener($pasteReferenceButton);
    }
  }

  /**
   * adds eventListener for copyFromAnotherPage
   */
  initClickEventListener($element) {
    const thisClass = this;
    // Add modal, functionality of the modal itself is not done here,
    // but rather in paste-reference-drag-drop and triggered by
    // the custom EventListener 'message' (see downwards)
    if ($element.length) {
      $element.on('click', function (evt) {
        evt.preventDefault();
        thisClass.copyFromAnotherPage($element);
      });
    }
  }

  /**
   * generates the paste into / paste after modal
   */
  copyFromAnotherPage($element) {
    const url = top.browserUrl + '&mode=db&bparams=' + $element.parent().attr('id') + '|||tt_content|';
    const configurationIframe = {
      type: Modal.types.iframe,
      content: url,
      size: Modal.sizes.large
    };
    Modal.advanced(configurationIframe);
  }
}

// Deactivate default functions
Paste.activatePasteModal = function (element) {}
Paste.activatePasteIcons = function () {}

export default PasteReference;

if (PasteReference.instanceCount === 0 && top.pasteReferenceAllowed) {
  const pollTime = 100;
  window.setTimeout(function() {
    if (PasteReference.instanceCount === 0) {
      const pasteReference = new PasteReference({});
    }
  }, pollTime);
}
