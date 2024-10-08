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

class OnReady {
  openedPopupWindow = [];

  /**
   * generates the paste into / paste after modal
   */
  copyFromAnotherPage(element) {
    const url = top.browserUrl + '&mode=db&bparams=' + element.parent().attr('id') + '|||tt_content|';
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
        let record = resolvedBody.data.tabs[0].items[0];
        let identifier = record.identifier;
        let table = identifier.split('|')[0];
        let uid = identifier.split('|')[1];
        let title = record.title.replace(/<[^>]*>?/gm, '');
        let clipboardData = {
          copyMode: resolvedBody.data.copyMode,
          data: record,
          itemOnClipboardUid: uid * 1,
          itemOnClipboardTitleHtml: record.title,
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
  };
}
const onReady = new OnReady;

/**
 * generates the paste into / paste after modal
 */
Paste.activatePasteModal = function (element) {
  const $element = $(element);
  const url = $element.data('url') || null;
  const elementTitle = this.itemOnClipboardTitle != undefined ? this.itemOnClipboardTitle : "["+TYPO3.lang['tx_paste_reference_js.modal.labels.no_title']+"]";
  const title = (TYPO3.lang['paste.modal.title.paste'] || 'Paste record') + ': "' + elementTitle + '"';
  const severity = (typeof top.TYPO3.Severity[$element.data('severity')] !== 'undefined') ? top.TYPO3.Severity[$element.data('severity')] : top.TYPO3.Severity.info;
  onReady.getClipboardData();
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
        btnClass: 'btn-' + Severity.getCssClass(severity),
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
    Modal.loadUrl(title, severity, buttons, url + separator + params);
  } else {
    Modal.show(title, content, severity, buttons);
  }
};

/**
 * activates the paste into / paste after and fetch copy from another page icons outside of the context menus
 */
Paste.activatePasteIcons = function () {

  $('.t3js-page-new-ce').each(function () { // t3-page-ce-wrapper-new-ce

    if (!$(this).find('.icon-actions-plus').length) { // icon-actions-add
      return true;
    }

    // TODO: check
    // what does it do in detail and why?
    // this does not work, clases have the form ".t3js-page-lang-column-0" - ".t3js-page-lang-column-x"
    $('.t3js-page-lang-column .t3-page-ce > .t3-page-ce').removeClass('t3js-page-ce');

    if (top.copyFromAnotherPageLinkTemplate) {

      // sorting of the buttons is important, else the modal for the first one is not working correctly
      // OLD: $(this).append(top.copyFromAnotherPageLinkTemplate);
      $(this).find('button.t3js-paste').after(top.copyFromAnotherPageLinkTemplate);

      // Add modal, functionality of the modal itself is not done here,
      // but rather in paste-reference-drag-drop and triggered by
      // the custom EventListener 'message' (see downwards)
      if ($(this).find('button.t3js-paste-new').length) {
        $(this).find('button.t3js-paste-new').on('click', function (evt) {
          evt.preventDefault();
          onReady.copyFromAnotherPage($(this));
        });
      }
    }

    // disable default click-EventListener
    $(document).off('click', '.t3js-paste');

    // add custom click-EventListener
    $(document).on('click', '.t3js-paste', (evt) => {
      evt.preventDefault();
      Paste.activatePasteModal($(evt.currentTarget));
    });
  });
};


/**
 * gives back the data from the popup window with record-selection to the copy action
 *
 * $('.typo3-TCEforms') is not relevant here as it exists on
 * detail pages for single records only.
 */
if (!$('.typo3-TCEforms').length) {
  window.addEventListener('message', function (evt) {

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
    DragDrop.default.onDrop(tableUid, $('#' + elementId).find('.t3js-paste-new'), 'copyFromAnotherPage');
  });
}

Paste.activatePasteIcons();

export default OnReady;
