import AjaxDataHandler from '@typo3/backend/ajax-data-handler.js';
import labels from '~labels/paste_reference.db';

const HEADER_MAX_LENGTH = 255;

/**
 * @typedef {Object} PasteTarget
 * @property {number} target positive page uid (start of the column) or negative content uid (after this element)
 * @property {number} colPos
 * @property {number} language
 * @property {number} [containerParent] uid of the EXT:container parent, if any
 */

/**
 * @param {string} title
 * @param {number} uid
 * @returns {string}
 */
export function buildReferenceHeader(title, uid) {
  const header = title
    ? labels.get('tx_paste_reference_js.reference_header', { title })
    : labels.get('tx_paste_reference_js.reference_header_no_title', { uid });
  return header.slice(0, HEADER_MAX_LENGTH);
}

/**
 * @param {PasteTarget} context
 * @returns {Object} the fields describing the position of the new element
 */
function buildPositionFields(context) {
  const fields = {
    colPos: context.colPos,
    sys_language_uid: context.language,
  };
  if (context.containerParent > 0) {
    fields.tx_container_parent = context.containerParent;
  }
  return fields;
}

/**
 * Same rule as the core DataHandler when pasting: an element for all languages
 * keeps -1 when it goes into the default language.
 *
 * @param {PasteTarget} context
 * @param {number|undefined} sourceLanguage sys_language_uid of the original, if known
 * @returns {PasteTarget}
 */
function applySourceLanguage(context, sourceLanguage) {
  return sourceLanguage === -1 && context.language === 0 ? { ...context, language: -1 } : context;
}

/**
 * @param {number} uid uid of the referenced tt_content record
 * @param {string} title title of the referenced record
 * @param {PasteTarget} context
 * @param {number} [sourceLanguage] sys_language_uid of the referenced record
 * @returns {Object} parameters for AjaxDataHandler.process()
 */
export function buildReferenceData(uid, title, context, sourceLanguage) {
  const newId = 'NEW' + Math.random().toString(36).slice(2);
  return {
    data: {
      tt_content: {
        [newId]: {
          pid: context.target,
          CType: 'shortcut',
          records: 'tt_content_' + uid,
          header: buildReferenceHeader(title, uid),
          ...buildPositionFields(applySourceLanguage(context, sourceLanguage)),
        },
      },
    },
  };
}

/**
 * @param {number} uid uid of the tt_content record to copy
 * @param {PasteTarget} context
 * @returns {Object} parameters for AjaxDataHandler.process()
 */
export function buildCopyCommand(uid, context) {
  return {
    cmd: {
      tt_content: {
        [uid]: {
          copy: {
            action: 'paste',
            target: context.target,
            update: buildPositionFields(context),
          },
        },
      },
    },
  };
}

/**
 * @returns {Promise<Object>} the DataHandler response, errors are already shown as notification
 */
export function pasteAsReference(uid, title, context, sourceLanguage) {
  return AjaxDataHandler.process(buildReferenceData(uid, title, context, sourceLanguage));
}

/**
 * @returns {Promise<Object>} the DataHandler response, errors are already shown as notification
 */
export function pasteAsCopy(uid, context) {
  return AjaxDataHandler.process(buildCopyCommand(uid, context));
}
