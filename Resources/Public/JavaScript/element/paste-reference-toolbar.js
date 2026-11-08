import { html, LitElement, nothing } from 'lit';
import '@typo3/backend/element/icon-element.js';
import Modal from '@typo3/backend/modal.js';
import Notification from '@typo3/backend/notification.js';
import Severity from '@typo3/backend/severity.js';
import { SeverityEnum } from '@typo3/backend/enum/severity.js';
import labels from '@ehaerer/paste-reference/labels.js';
import { pasteAsCopy, pasteAsReference } from '@ehaerer/paste-reference/command.js';
import { fetchRecordInfo, pickRecord } from '@ehaerer/paste-reference/record-picker.js';

/**
 * Reads the paste target from the same data attributes the core paste module uses.
 *
 * @param {HTMLElement} element
 * @returns {import('@ehaerer/paste-reference/command.js').PasteTarget}
 */
export function readPasteTarget(element) {
  const contentElement = element.closest('.t3js-page-ce');
  const uid = contentElement?.dataset.uid;
  return {
    target: uid !== undefined ? -parseInt(uid, 10) : parseInt(contentElement?.dataset.page ?? '0', 10),
    colPos: parseInt(element.closest('[data-colpos]')?.dataset.colpos ?? '0', 10),
    language: parseInt(element.closest('[data-language-uid]')?.dataset.languageUid ?? '0', 10),
    containerParent: parseInt(element.closest('[data-tx-container-parent]')?.dataset.txContainerParent ?? '0', 10),
  };
}

/**
 * Additive toolbar next to the "new content element" button of the page module.
 */
export class PasteReferenceToolbar extends LitElement {
  static properties = {
    config: { attribute: false },
  };

  createRenderRoot() {
    return this;
  }

  get hasClipboardCopy() {
    return this.config.clipboard?.copyMode === 'copy';
  }

  get availableActions() {
    return { copy: true, reference: this.config.referenceAllowed };
  }

  render() {
    const context = readPasteTarget(this);
    const showPaste = this.hasClipboardCopy;
    const pasteTitle = labels.get(context.target > 0 ? 'tx_paste_reference_js.pasteinto' : 'tx_paste_reference_js.pasteafter');
    const pickTitle = labels.get('tx_paste_reference_js.copyfrompage');
    return html`
      ${showPaste ? html`
        <button type="button" class="btn btn-default btn-sm" title=${pasteTitle} aria-label=${pasteTitle}
          @click=${this.pasteFromClipboard}>
          <typo3-backend-icon identifier="actions-document-paste-into" size="small"></typo3-backend-icon>
        </button>` : nothing}
      ${this.config.copyFromPageEnabled ? html`
        <button type="button" class="btn btn-default btn-sm" title=${pickTitle} aria-label=${pickTitle}
          @click=${this.pasteFromOtherPage}>
          <typo3-backend-icon identifier="actions-insert-reference" size="small"></typo3-backend-icon>
        </button>` : nothing}
    `;
  }

  pasteFromClipboard() {
    const { uid, title, language } = this.config.clipboard;
    this.choosePasteAction(uid, title, this.availableActions, this.config.confirmPaste, language);
  }

  async pasteFromOtherPage() {
    const record = await pickRecord(this.config.elementBrowserUrl);
    if (record === null) {
      return;
    }
    const info = await fetchRecordInfo(record.uid);
    if (info === null) {
      Notification.error(labels.get('tx_paste_reference_js.notification.record_info_failed'));
      return;
    }
    this.choosePasteAction(info.uid, info.title, this.availableActions, true, info.sys_language_uid);
  }

  choosePasteAction(uid, title, actions, confirm, sourceLanguage) {
    const context = readPasteTarget(this);
    const execute = {
      copy: () => this.finish(pasteAsCopy(uid, context)),
      reference: () => this.finish(pasteAsReference(uid, title, context, sourceLanguage)),
    };
    const available = Object.keys(execute).filter((action) => actions[action]);
    if (available.length === 0) {
      return;
    }
    if (available.length === 1 && !confirm) {
      execute[available[0]]();
      return;
    }

    const button = (text, btnClass, action) => ({
      text,
      btnClass,
      trigger: (event, modal) => {
        modal.hideModal();
        action?.();
      },
    });
    const warningClass = 'btn-' + Severity.getCssClass(SeverityEnum.warning);
    const buttons = [{ ...button(labels.get('tx_paste_reference_js.modal.button.cancel'), 'btn-default'), active: true }];
    if (actions.copy) {
      buttons.push(button(labels.get('tx_paste_reference_js.modal.button.pastecopy'), warningClass, execute.copy));
    }
    if (actions.reference) {
      buttons.push(button(labels.get('tx_paste_reference_js.modal.button.pastereference'), warningClass, execute.reference));
    }
    Modal.show(
      labels.get('tx_paste_reference_js.modal.title', {
        title: title || labels.get('tx_paste_reference_js.modal.labels.no_title'),
      }),
      labels.get('tx_paste_reference_js.modal.pastecopy'),
      SeverityEnum.warning,
      buttons,
    );
  }

  async finish(process) {
    const result = await process;
    if (!result.hasErrors) {
      window.location.reload();
    }
  }
}

customElements.define('paste-reference-toolbar', PasteReferenceToolbar);
