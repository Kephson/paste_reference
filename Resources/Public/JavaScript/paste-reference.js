import Modal from '@typo3/backend/modal.js';
import Severity from '@typo3/backend/severity.js';
import Helper from '@ehaerer/paste-reference/helper.js';
import DocumentService from '@typo3/core/document-service.js';

/**
 * JavaScript to handle PasteReference related actions for Contextmenu
 * @exports @ehaerer/paste-reference/paste-reference
 */
class PasteReferenceHandler {

  itemOnClipboardUid = 0;
  itemOnClipboardTitle = '';
  copyMode = '';
  buttonClassIdentifier = '.t3js-page-new-ce';
  pasteAfterLinkTemplate = '';
  pasteIntoLinkTemplate = '';

  /**
   * initializes paste icons for all content elements on the page
   */
  constructor(args) {

    //console.log(args);

    this.itemOnClipboardUid = args.itemOnClipboardUid;
    this.itemOnClipboardTitle = args.itemOnClipboardTitle;
    this.copyMode = args.copyMode;


    DocumentService.ready().then(() => {
      if (document.querySelectorAll('.t3js-page-columns').length > 0) {
        this.generateButtonTemplates();
        this.activatePasteIcons();
        this.initializeEvents();
      }
    });

  }

  /**
   * create buttons for inserting a reference instead of copy
   */
  generateButtonTemplates() {

    if (!this.itemOnClipboardUid) {
      return;
    }
    const btnTitle1 = (TYPO3.lang["tx_paste_reference_js.pasteafter"] || "Paste reference after this record");
    const btnTitle2 = (TYPO3.lang["tx_paste_reference_js.pastereference"] || "Paste reference into this column");
    this.pasteAfterLinkTemplate = '<button'
      + ' type="button"'
      + ' class="t3js-paste-reference t3js-paste-reference' + (this.copyMode ? '-' + this.copyMode : '') + ' t3js-paste-reference-after btn btn-default btn-sm"'
      + ' title="' + btnTitle1 + '">'
      + '<typo3-backend-icon identifier="actions-document-share" size="small"></typo3-backend-icon>'
      + '</button>';
    this.pasteIntoLinkTemplate = '<button'
      + ' type="button"'
      + ' class="t3js-paste-reference t3js-paste-reference' + (this.copyMode ? '-' + this.copyMode : '') + ' t3js-paste-reference-into btn btn-default btn-sm"'
      + ' title="' + btnTitle2 + '">'
      + '<typo3-backend-icon identifier="actions-document-share" size="small"></typo3-backend-icon>'
      + '</button>';
  };

  /**
   * activates the paste into / paste after icons outside the context menus
   */
  activatePasteIcons() {
    if (this.pasteAfterLinkTemplate && this.pasteIntoLinkTemplate) {
      document.querySelectorAll(this.buttonClassIdentifier).forEach((el => {
        const template = el.parentElement.dataset.page ? this.pasteIntoLinkTemplate : this.pasteAfterLinkTemplate;
        el.append(document.createRange().createContextualFragment(template));
      }));
    }
  }

  initializeEvents() {
    document.querySelectorAll('.t3js-paste-reference').forEach(item => {
      item.addEventListener('click', e => {
        this.activatePasteModal(item);
      })
    });
  }

  /**
   *
   * @param element
   */
  activatePasteModal(element) {
    const performPasteReference = (element) => {
      console.log(element);
      /*const actionUrl = dataset.actionUrl;
      const url = actionUrl + '&redirect=' + ContextMenuActions.getReturnUrl();
      top.TYPO3.Backend.ContentContainer.setUrl(url);
       */
    }

    let title = (TYPO3.lang["tx_paste_reference_js.modal.button.pastereference"] || "Paste reference");
    let message = (TYPO3.lang["newContentElementReference"] || "Paste in clipboard content as reference") + ': "' + this.itemOnClipboardTitle + '"';

    Modal.confirm(
      title,
      message,
      Severity.warning, [
        {
          text: TYPO3.lang['button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: function (event, modal) {
            modal.hideModal();
          }
        },
        {
          text: TYPO3.lang['button.ok'] || 'OK',
          btnClass: 'btn-warning',
          name: 'ok',
          trigger: function (event, modal) {
            modal.hideModal();
            performPasteReference(element);
          }
        }
      ]);
  }

}

export default PasteReferenceHandler;
