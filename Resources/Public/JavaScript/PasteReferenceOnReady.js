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

/**
 * this JS code initializes several settings for the Layout module (Web => Page)
 * based on jQuery UI
 */

define(['jquery', 'TYPO3/CMS/Backend/AjaxDataHandler', 'TYPO3/CMS/Backend/Storage/Persistent', 'TYPO3/CMS/PasteReference/PasteReferenceDragDrop', 'TYPO3/CMS/Backend/LayoutModule/Paste', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Severity'], function ($, AjaxDataHandler, PersistentStorage, DragDrop, Paste, Modal, Severity) {

	var OnReady = {
		openedPopupWindow: []
	};

	/**
	 * activates the paste into / paste after and fetch copy from another page icons outside of the context menus
	 */
	Paste.activatePasteIcons = function () {
		$('.icon-actions-document-paste-into').parent().remove();
		$('.t3-page-ce-wrapper-new-ce').each(function () {
			if (!$(this).find('.icon-actions-add').length) {
				return true;
			}
			$(this).addClass('btn-group btn-group-sm');
			$('.t3js-page-lang-column .t3-page-ce > .t3-page-ce').removeClass('t3js-page-ce');
			if (top.pasteAfterLinkTemplate && top.pasteIntoLinkTemplate) {
				var parent = $(this).parent();
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
				var parent = $(this).parent();
				if (parent.data('page') || (parent.data('container') && !parent.data('uid'))) {
					$(this).append(Paste.pasteIntoLinkTemplate);
				} else {
					$(this).append(Paste.pasteAfterLinkTemplate);
				}
			}
			$(this).append(top.copyFromAnotherPageLinkTemplate);
			$(this).find('.t3js-paste-new').on('click', function (evt) {
				evt.preventDefault();
				OnReady.copyFromAnotherPage($(this));
			});
		});
	};

	/**
	 * generates the paste into / paste after modal
	 */
	Paste.activatePasteModal = function (element) {
		var $element = $(element);
		var url = $element.data('url') || null;
		var title = (TYPO3.lang['paste.modal.title.paste'] || 'Paste record') + ': "' + $element.data('title') + '"';
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
					btnClass: 'btn-' + top.TYPO3.Severity.getCssClass(severity),
					trigger: function (ev) {
						Modal.currentModal.trigger('modal-dismiss');
						DragDrop.default.onDrop($element.data('content'), $element, ev);
					}
				},
				{
					text: TYPO3.lang['tx_paste_reference_js.modal.button.pastereference'] || 'Paste as reference',
					btnClass: 'btn-' + top.TYPO3.Severity.getCssClass(severity),
					trigger: function (ev) {
						Modal.currentModal.trigger('modal-dismiss');
						DragDrop.default.onDrop($element.data('content'), $element, ev, 'reference');
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
	 * generates the paste into / paste after modal
	 */
	OnReady.copyFromAnotherPage = function (element) {
		var url = top.browserUrl + '&mode=db&bparams=' + element.parent().attr('id') + '|||tt_content|';
		OnReady.openedPopupWindow = window.open(url, 'Typo3WinBrowser', 'height=600,width=800,status=0,menubar=0,resizable=1,scrollbars=1');
		OnReady.openedPopupWindow.focus();
	};

	/**
	 * gives back the data from the popup window to the copy action
	 */
	if (!$('.typo3-TCEforms').length) {
		OnReady.setSelectOptionFromExternalSource = setFormValueFromBrowseWin = function (elementId, tableUid) {
			tableUid = tableUid.replace('tt_content_', '') * 1;
			DragDrop.default.onDrop(tableUid, $('#' + elementId).find('.t3js-paste-new'), 'copyFromAnotherPage');
		}
	}

	$(OnReady.initialize);
	return OnReady;
});
