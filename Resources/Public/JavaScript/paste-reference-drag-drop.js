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
 * this JS code does the drag+drop logic for the Layout module (Web => Page)
 * based on jQuery UI
 */

import DragDrop from "@typo3/backend/layout-module/drag-drop.js";
import Paste from "@typo3/backend/layout-module/paste.js";
import AjaxDataHandler from "@typo3/backend/ajax-data-handler.js";

/**
 * Module: @ehaerer/paste-reference/paste-reference-drag-drop.js
 */

'use strict';

/**
 * @exports @ehaerer/paste-reference/paste-reference-drag-drop.js
 */
DragDrop.default = {
  contentIdentifier: '.t3js-page-ce',
  draggableIdentifier: '.t3js-page-ce:has(.t3-page-ce-header-draggable)',
  newContentElementWizardIdentifier: '#new-element-drag-in-wizard',
  cTypeIdentifier: '.t3-ctype-identifier',
  contentWrapperIdentifier: '.t3-page-ce-wrapper',
  disabledNewContentIdentifier: '.t3-page-ce-disable-new-ce',
  newContentElementOnclick: '',
  newContentElementDefaultValues: {},
  drag: {},
  types: {},
  ownDropZone: {},
  column: {},

  /**
   * initializes Drag+Drop for all content elements on the page
   */
  initialize: function () {
    $(DragDrop.default.draggableIdentifier).draggable({
      handle: this.dragHeaderIdentifier,
      scope: 'tt_content',
      cursor: 'move',
      distance: 20,
      addClasses: 'active-drag',
      revert: 'invalid',
    });
    $(DragDrop.default.dropZoneIdentifier).droppable({
      accept: this.contentIdentifier,
      scope: 'tt_content',
      tolerance: 'pointer',
    });
  },

  /**
   * this method does the whole logic when a draggable is dropped on to a dropzone
   * sending out the request and afterwards move the HTML element in the right place.
   *
   * @param $draggableElement
   * @param $droppableElement
   * @param {Event} evt the event
   * @param reference if content should be pasted as copy or reference
   * @private
   */
  onDrop: function (draggableElement, droppableElement, evt, reference) {
    const newColumn = DragDrop.default.getColumnPositionForElement(droppableElement) ?? 0;

    droppableElement.classList.remove(DragDrop.default.dropPossibleHoverClass);
    const pasteAction = typeof draggableElement === 'number' || typeof draggableElement === 'undefined';
    let pasteElement = null;
    if (draggableElement) {
      pasteElement = draggableElement;
    } else if (typeof top.itemOnClipboardUid === 'number') {
      pasteElement = top.itemOnClipboardUid;
    }
    // send an AJAX request via the AjaxDataHandler
    let contentElementUid = pasteAction ? pasteElement : null;
    if (!contentElementUid && typeof draggableElement.dataset.uid !== 'undefined') {
      contentElementUid = parseInt(draggableElement.dataset.uid ?? 0);
    }
    if (contentElementUid > 0 || (DragDrop.default.newContentElementDefaultValues.CType && !pasteAction)) {
      let parameters = {};
      // add the information about a possible column position change
      const targetFound = droppableElement.closest(DragDrop.default.contentIdentifier)?.dataset.uid;
      // the item was moved to the top of the colPos, so the page ID is used here
      let targetPid = 0;
      if (typeof targetFound === 'undefined') {
          // the actual page is needed
          targetPid = document.querySelector('.t3js-page-ce[data-page]').dataset.page;
      } else {
          // the negative value of the content element after where it should be moved
          targetPid = 0 - parseInt(targetFound);
      }
      const closestElementWithLanguage = draggableElement || droppableElement.closest('[data-language-uid]');
      let language = closestElementWithLanguage;
      if (language !== parseInt(closestElementWithLanguage)) {
          language = parseInt(closestElementWithLanguage.dataset.language-uid);
      }
      if (language !== -1) {
          language = parseInt(droppableElement.closest('[data-language-uid]').dataset.languageUid);
      }
      let colPos = 0;
      if (targetPid !== 0) {
          colPos = newColumn;
      }
      parameters['cmd'] = {tt_content: {}};
      parameters['data'] = {tt_content: {}};
      let copyAction = (evt && evt.originalEvent && evt.originalEvent.ctrlKey || droppableElement.classList.contains('t3js-paste-copy') || evt === 'copyFromAnotherPage');
      if (DragDrop.default.newContentElementDefaultValues.CType) {
        parameters['data']['tt_content']['NEW234134'] = DragDrop.default.newContentElementDefaultValues;
        parameters['data']['tt_content']['NEW234134']['pid'] = targetPid;
        parameters['data']['tt_content']['NEW234134']['colPos'] = colPos;
        parameters['data']['tt_content']['NEW234134']['sys_language_uid'] = language;

        if (!parameters['data']['tt_content']['NEW234134']['header']) {
          parameters['data']['tt_content']['NEW234134']['header'] = TYPO3.l10n.localize('tx_paste_reference_js.newcontentelementheader');
        }

        parameters['DDinsertNew'] = 1;

        // fire the request, and show a message if it has failed
        AjaxDataHandler.process(parameters).then(function (result) {
          if (!result.hasErrors) {
            // insert draggable on the new position
            if (!pasteAction) {
              if (!droppableElement.parent().hasClass(DragDrop.default.contentIdentifier.substring(1))) {
                draggableElement.detach().css({top: 0, left: 0})
                    .insertAfter(droppableElement.closest(DragDrop.default.dropZoneIdentifier));
              } else {
                draggableElement.detach().css({top: 0, left: 0})
                    .insertAfter(droppableElement.closest(DragDrop.default.contentIdentifier));
              }
            }
            self.location.hash = droppableElement.closest(DragDrop.default.contentIdentifier).attr('id');
            self.location.reload(true);
          }
        });
      } else if (copyAction) {
          parameters['cmd']['tt_content'][contentElementUid] = {
            copy: {
              action: 'paste',
              target: targetPid,
              update: {
                colPos: colPos,
                sys_language_uid: language
              }
            }
          };
          if (reference === 'reference') {
            parameters['reference'] = 1;
          }
          if (evt === 'copyFromAnotherPage') {
            parameters['CB'] = {setCopyMode: 1};
          }
          // fire the request, and show a message if it has failed
          // This is adding a copy from another page "to this [selected] place".
          AjaxDataHandler.process(parameters).then(function (result) {
            if (!result.hasErrors) {
              // insert draggable on the new position
              if (!pasteAction) {
                if (!droppableElement.parent().hasClass(DragDrop.default.contentIdentifier.substring(1))) {
                  draggableElement.detach().css({top: 0, left: 0})
                    .insertAfter(droppableElement.closest(DragDrop.default.dropZoneIdentifier));
                } else {
                  draggableElement.detach().css({top: 0, left: 0})
                    .insertAfter(droppableElement.closest(DragDrop.default.contentIdentifier));
                }
              }
              self.location.hash = droppableElement.closest(DragDrop.default.contentIdentifier).id;
              self.location.reload(true);
            }
          });
      } else {
        parameters['cmd']['tt_content'][contentElementUid] = {
          move: {
            action: 'paste',
            target: targetPid,
            update: {
              colPos: colPos,
              sys_language_uid: language
            }
          }
        };
        // fire the request, and show a message if it has failed
        AjaxDataHandler.process(parameters).then(function (result) {
          if (!result.hasErrors) {
            // insert draggable on the new position
            if (!pasteAction) {
              if (!droppableElement.parent().hasClass(DragDrop.default.contentIdentifier.substring(1))) {
                draggableElement.detach().css({top: 0, left: 0})
                  .insertAfter(droppableElement.closest(DragDrop.default.dropZoneIdentifier));
              } else {
                draggableElement.detach().css({top: 0, left: 0})
                  .insertAfter(droppableElement.closest(DragDrop.default.contentIdentifier));
              }
            }
            self.location.hash = droppableElement.closest(DragDrop.default.contentIdentifier).attr('id');
            self.location.reload();
          }
        });
      }
    }
  },

  /**
   * returns the next "upper" container colPos parameter inside the code
   * @param element
   * @return int|boolean the colPos
   */
  getColumnPositionForElement: function (element) {
    const columnContainer = element && element.closest('[data-colpos]') ? element.closest('[data-colpos]') : [];
    if (columnContainer.length && columnContainer.dataset.colpos !== 'undefined') {
      return columnContainer.dataset.colpos;
    } else {
      return false;
    }
  }
}

export default DragDrop;
