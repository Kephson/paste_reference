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
import DocumentService from "@typo3/core/document-service.js";
import DataHandler from "@typo3/backend/ajax-data-handler.js";
import { default as Modal } from "@typo3/backend/modal.js";
import Severity from "@typo3/backend/severity.js";
import "@typo3/backend/element/icon-element.js";
import { SeverityEnum } from "@typo3/backend/enum/severity.js";
import RegularEvent from "@typo3/core/event/regular-event.js";
import Paste from "@typo3/backend/layout-module/paste.js";
// import DragDrop from "@typo3/backend/layout-module/drag-drop.js";
import DragDrop from "@ehaerer/paste-reference/paste-reference-drag-drop.js";
import { MessageUtility } from "@typo3/backend/utility/message-utility.js";
import { loadModule, JavaScriptItemProcessor } from "@typo3/core/java-script-item-processor.js";

class OnReady {
  openedPopupWindow = [];

  /**
   * generates the paste into / paste after modal
   */
  copyFromAnotherPage(element) {
    var url = top.browserUrl + '&mode=db&bparams=' + element.parent().attr('id') + '|||tt_content|';
    var configurationIframe = {
      type: Modal.types.iframe,
      content: url,
      size: Modal.sizes.large
    };
    console.log(configurationIframe);
    Modal.advanced(configurationIframe);
  };
}
const onReady = new OnReady;

class PasteExtended extends Paste {
  constructor(args) {
    super(args);
  };
  getCopyMode() {
    return this.copyMode;
  };
}
// export PasteExtended;

Paste.generateButtonTemplates = function() {
    let copyMode = top.copyMode;
    let icon = '<typo3-backend-icon identifier="actions-document-paste-into" size="small"></typo3-backend-icon>';
    let defaultClasses = 'btn btn-default btn-sm t3js-paste t3js-paste' + (copyMode ? "-" + copyMode : "");
    top.pasteAfterLinkTemplate = '<button type="button"'
      + ' class="t3js-paste-after default-after ' + defaultClasses + '"'
      + ' title="' + TYPO3.lang?.pasteAfterRecord + '">' + icon + '</button>';
    top.pasteIntoLinkTemplate = '<button type="button"'
      + ' class="t3js-paste-into default-into ' + defaultClasses + '"'
      + ' title="' + TYPO3.lang?.pasteIntoColumn + '">' + icon + '</button>';
};

/**
 * activates the paste into / paste after and fetch copy from another page icons outside of the context menus
 */
Paste.activatePasteIcons = function () {
// console.log('Paste.activatePasteIcons');
  $('.icon-actions-document-paste-into').parent().remove();
// console.log($('.t3js-page-new-ce'));
  $('.t3js-page-new-ce').each(function () { // t3-page-ce-wrapper-new-ce
    if (!$(this).find('.icon-actions-plus').length) { // icon-actions-add
      console.log('not found: .icon-actions-plus');
      return true;
    }
    $(this).addClass('btn-group btn-group-sm btn-pasteReference');
    // TODO: check
    $('.t3js-page-lang-column .t3-page-ce > .t3-page-ce').removeClass('t3js-page-ce');
/*
console.log({
  'top': top,
  'top.pasteReferenceAllowed': top.pasteReferenceAllowed,
  'top.pasteAfterLinkTemplate': top.pasteAfterLinkTemplate,
  'top.pasteIntoLinkTemplate': top.pasteIntoLinkTemplate,
  'Paste': Paste,
  'Paste.pasteAfterLinkTemplate': Paste.hasOwnProperty('pasteAfterLinkTemplate'),
  'Paste.pasteIntoLinkTemplate': Paste.hasOwnProperty('pasteIntoLinkTemplate'),
  'Paste.copyMode': Paste.copyMode,
})
*/

    // var parentElement = $(this).parent();
    // Paste.generateButtonTemplates(parentElement);
    // console.log({'this': $(this), 'parentElement': parentElement});


    /*
    if (top.pasteAfterLinkTemplate && top.pasteIntoLinkTemplate) {
      if (parent.data('page') || (parent.data('container') && !parent.data('uid'))) {
        $(this).append(top.pasteIntoLinkTemplate);
      } else {
        $(this).append(top.pasteAfterLinkTemplate);
      }
      $(this).find('.t3js-paste').on('click', function (evt) {
        evt.preventDefault();
        Paste.activatePasteModal($(this));
      });
    }
    if (Paste.pasteAfterLinkTemplate && Paste.pasteIntoLinkTemplate) {
      if (parent.data('page') || (parent.data('container') && !parent.data('uid'))) {
        $(this).append(Paste.pasteIntoLinkTemplate);
      } else {
        $(this).append(Paste.pasteAfterLinkTemplate);
      }
    }
    */

    // TODO: wait till the other icon appeared
    if (top.copyFromAnotherPageLinkTemplate) {
      $(this).append(top.copyFromAnotherPageLinkTemplate);
    }
    /*
    console.log('top', top);
    // ----------------------------------------------------
    // BOTH top.* variables never exist anymore (they are available in Paste.* instead)
    // ALSO the icons would be added instead of replaced
    // ----------------------------------------------------
    if (top.pasteAfterLinkTemplate && top.pasteIntoLinkTemplate) {
      if (parent.data('page') || (parent.data('container') && !parent.data('uid'))) {
        $(this).append(top.pasteIntoLinkTemplate);
        console.log(top.pasteIntoLinkTemplate);
      } else {
        $(this).append(top.pasteAfterLinkTemplate);
        console.log(top.pasteAfterLinkTemplate);
      }
    }
    */
    if ($(this).find('.t3js-paste-new').length) {
      $(this).find('.t3js-paste-new').on('click', function (evt) {
        evt.preventDefault();
        // Paste.activatePasteModal($(this));   <<< has 2 Modals, hide / avoid the one without style
        onReady.copyFromAnotherPage($(this));
      });
    }
    else if ($(this).find('.t3js-page-new-ce').length) {
      $(this).find('.t3js-paste-new-ce').on('click', function (evt) {
        evt.preventDefault();
        Paste.activatePasteModal($(this));
        onReady.copyFromAnotherPage($(this));
      });
    }
  });
};

/**
 * generates the paste into / paste after modal
 */
Paste.activatePasteModal = function (element) {
  console.log('Paste.activatePasteModal');
  var $element = $(element);
  var url = $element.data('url') || null;
  var elementTitle = this.itemOnClipboardTitle != undefined ? this.itemOnClipboardTitle : "["+TYPO3.lang['tx_paste_reference_js.modal.labels.no_title']+"]";
  var title = (TYPO3.lang['paste.modal.title.paste'] || 'Paste record') + ': "' + elementTitle + '"';
  var severity = (typeof top.TYPO3.Severity[$element.data('severity')] !== 'undefined') ? top.TYPO3.Severity[$element.data('severity')] : top.TYPO3.Severity.info;
  if ($element.hasClass('t3js-paste-copy')) {
    var content = TYPO3.lang['tx_paste_reference_js.modal.pastecopy'] || '1 How do you want to paste that clipboard content here?';
    var buttons = [
      {
        text: TYPO3.lang['paste.modal.button.cancel'] || 'Cancel',
        active: true,
        btnClass: 'btn-default',
        trigger: function () {
          Modal.currentModal.trigger('modal-dismiss');
        }
      },
      {
        text: TYPO3.lang['tx_paste_reference_js.modal.button.pastecopy'] || 'Paste as copy',
        btnClass: 'text-white btn-' + top.TYPO3.Severity.getCssClass(severity),
        trigger: function (evt) {
          Modal.currentModal.trigger('modal-dismiss');
          DragDrop.default.onDrop($element.data('content'), $element, evt);
        }
      },
      {
        text: TYPO3.lang['tx_paste_reference_js.modal.button.pastereference'] || 'Paste as reference',
        btnClass: 'text-white btn-' + top.TYPO3.Severity.getCssClass(severity),
        trigger: function (evt) {
          Modal.currentModal.trigger('modal-dismiss');
          DragDrop.default.onDrop($element.data('content'), $element, evt, 'reference');
        }
      }
    ];
    if (top.pasteReferenceAllowed !== true) {
      buttons.pop();
    }
  } else {
    var content = TYPO3.lang['paste.modal.paste'] || 'Do you want to move the record to this position?';
    var buttons = [
      {
        text: TYPO3.lang['paste.modal.button.cancel'] || 'Cancel',
        active: true,
        btnClass: 'btn-default',
        trigger: function () {
          Modal.currentModal.trigger('modal-dismiss');
        }
      },
      {
        text: TYPO3.lang['paste.modal.button.paste'] || 'Move',
        btnClass: 'btn-' + Severity.getCssClass(severity),
        trigger: function () {
          Modal.currentModal.trigger('modal-dismiss');
          DragDrop.default.onDrop($element.data('content'), $element, null);
        }
      }
    ];
  }
  if (url !== null) {
    var separator = (url.indexOf('?') > -1) ? '&' : '?';
    var params = $.param({data: $element.data()});
    Modal.loadUrl(title, severity, buttons, url + separator + params);
  } else {
    Modal.show(title, content, severity, buttons);
  }
};


/**
 * gives back the data from the popup window with record-selection to the copy action
 *
 * TODO: add option to add reference instead of copy.
 *       Question: how to realize and style the GUI for it?
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
    var tableUid = result.replace('tt_content_', '') * 1;
    var elementId = evt.data.fieldName;
    DragDrop.default.onDrop(tableUid, $('#' + elementId).find('.t3js-paste-new'), 'copyFromAnotherPage');
  });
}

// $(OnReady.initialize);
Paste.activatePasteIcons();

export default OnReady;
