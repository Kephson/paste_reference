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
    (DragDrop.default.draggableIdentifier).draggable({
      handle: this.dragHeaderIdentifier,
      scope: 'tt_content',
      cursor: 'move',
      distance: 20,
      addClasses: 'active-drag',
      revert: 'invalid',
    });
    (DragDrop.default.dropZoneIdentifier).droppable({
      accept: this.contentIdentifier,
      scope: 'tt_content',
      tolerance: 'pointer',
    });
  },

  parseDraggableIdentifier: function(draggableIdentifier) {
    let colPos = null;
    let draggableElement = null;
    if ((draggableIdentifier + " ").indexOf('|') !== -1) {
      colPos = (draggableIdentifier + " ").split('|')[0];
      draggableElement =  (" " + draggableIdentifier).split('|')[1];
    } else {
      draggableElement = draggableIdentifier;
    }
// console.log({colPos: colPos, draggableElement: draggableElement, draggableIdentifier: draggableIdentifier + " "});
    return {colPos: colPos, draggableElement: draggableElement};
  },

  /**
   * this method does the whole logic when a draggable is dropped on to a dropzone
   * sending out the request and afterwards move the HTML element in the right place.
   *
   * @param draggableIdentifier
   * @param droppableElement
   * @param {Event} evt the event
   * @param reference if content should be pasted as copy or reference
   * @private
   */
  onDrop: function (
    draggableIdentifier,
    droppableElement,
    itemUid,
    evt,
    reference
  ) {

    // pasteElement
    const parsedDraggableIdentifier = this.parseDraggableIdentifier(draggableIdentifier);
    let draggableElement = parsedDraggableIdentifier.draggableElement;
    let pasteElement = null;
    if (draggableElement) {
      if (draggableElement.toString().match(/^[0-9]+$/) > -1) {
        draggableElement = draggableElement * 1;
      }
      pasteElement = draggableElement;
    } else if (itemUid.match(/^[0-9]+$/) > -1) {
      pasteElement = itemUid * 1;
    }
    // colPos
    let colPos = parsedDraggableIdentifier.colPos ?? (DragDrop.default.getColumnPositionForElement(droppableElement) ?? 0);

    const pasteAction = typeof draggableElement === 'number' || typeof draggableElement === 'undefined';
    let contentElementUid = pasteAction ? pasteElement : null;
    // if (!contentElementUid && typeof draggableElement.dataset === 'function') {
    if (!contentElementUid) {
      if (draggableElement.dataset && typeof draggableElement.dataset['uid'] !== 'undefined') {
        contentElementUid = parseInt(draggableElement.dataset['uid'] ?? 0);
      }
    }
    /*
    console.log(typeof itemUid);
    let contentElementUid = $pasteAction ? $pasteElement : null;
    if (!contentElementUid && typeof $draggableElement.data === 'function') {
      contentElementUid = parseInt($draggableElement.data('uid') ?? 0);
    }
    */

    droppableElement.classList.remove(DragDrop.default.dropPossibleHoverClass);

    const containerParent = DragDrop.default.getContainerParentForElement(droppableElement) ?? 0;

    // send an AJAX request via the AjaxDataHandler
    if (contentElementUid > 0 || (DragDrop.default.newContentElementDefaultValues.CType && !pasteAction)) {
      // add the information about a possible column position change
      const targetFound = droppableElement.closest(DragDrop.default.contentIdentifier)?.dataset['uid'];
      // the item was moved to the top of the colPos, so the page ID is used here
      let targetPid = 0;
      if (typeof targetFound === 'undefined') {
        // the actual page is needed
        targetPid = document.querySelector('.t3js-page-ce[data-page]').dataset['page'];
      } else {
        // the negative value of the content element after where it should be moved
        targetPid = 0 - parseInt(targetFound);
      }
      const elementsWithLanguage = document.querySelectorAll('[data-language-uid]');
      let languageUid = 0;
      for (let index in elementsWithLanguage) {
        languageUid = parseInt(elementsWithLanguage[index].dataset.languageUid);
        if (languageUid !== -1) {
          break;
        }
      }

// console.log({colPos: colPos});
      let task = {
        'new': DragDrop.default.newContentElementDefaultValues.CType,
        'copy': (evt && ((evt.originalEvent && evt.originalEvent.ctrlKey) || evt === 'copyFromAnotherPage')
                  || droppableElement.classList.contains('t3js-paste-copy')
                )
      };

/*
console.log({
  'draggableIdentifier': draggableIdentifier,
  'parsedDraggableIdentifier': parsedDraggableIdentifier,
  'colPos': colPos,
  'type colPos': typeof colPos,
  // 'newColumn': newColumn,
  'draggableElement': draggableElement,
  'droppableElement': droppableElement,
  'itemUid': itemUid,
  'contentElementUid': contentElementUid,
  'targetPid': targetPid,
  'containerParent': containerParent,
  'languageUid':languageUid,
});
*/
      if (task.new) {
        this.newAction(draggableElement, droppableElement, pasteAction, targetPid, colPos, languageUid);
      } else if (task.copy) {
        this.copyAction(draggableElement, droppableElement, pasteAction, targetPid, colPos, languageUid, containerParent, evt, reference);
      } else {
        this.moveAction(draggableElement, droppableElement, pasteAction, targetPid, colPos, languageUid, containerParent);
      }
    }
  },

  newAction: function(
    draggableElement,
    droppableElement,
    pasteAction,
    targetPid,
    colPos,
    languageUid
  ) {

    let generalParameters = {
      data: {
        tt_content: {
          "NEW234134": DragDrop.default.newContentElementDefaultValues,
        },
      },
      cmd: {tt_content: {}},
    };
    let defaultParameters = {
      data: {
        tt_content: {
          "NEW234134": {
            pid: targetPid,
            colPos: colPos,
            sys_language_uid: languageUid,
          }
        },
      },
    }
    let parameters = generalParameters.concat(defaultParameters);
    if (!parameters.data.tt_content.NEW234134.header) {
      parameters.data.tt_content.NEW234134.header = TYPO3.l10n.localize('tx_paste_reference_js.newcontentelementheader');
    }

    this.ajaxAction(parameters, draggableElement, droppableElement, pasteAction);
  },

  copyAction: function(
    draggableElement,
    droppableElement,
    pasteAction,
    targetPid,
    colPos,
    languageUid,
    containerParent,
    evt,
    reference
  ) {

    const parameters = {
        CB: {
            paste: 'tt_content|' + targetPid,
            pad: 'normal',
            update: {
                colPos: colPos,
                sys_language_uid: languageUid,
            },
        },
    };
    if (reference === 'reference') {
      parameters['reference'] = 1;
    }
    if (evt === 'copyFromAnotherPage') {
      parameters['CB'] = {setCopyMode: 1};
    }

    this.ajaxAction(parameters, draggableElement, droppableElement, pasteAction, containerParent);
  },

  moveAction: function(
    draggableElement,
    droppableElement,
    pasteAction,
    targetPid,
    colPos,
    languageUid,
    containerParent
  ) {

    const parameters = {
        CB: {
            paste: 'tt_content|' + targetPid,
            pad: 'normal',
            update: {
                colPos: colPos,
                sys_language_uid: languageUid,
            },
        },
    };

    this.ajaxAction(parameters, draggableElement, droppableElement, pasteAction, containerParent);
  },

  ajaxAction: function(
    parameters,
    draggableElement,
    droppableElement,
    pasteAction,
    containerParent = null
  ) {
    if (containerParent) {
      parameters.cmd.tt_content.contentElementUid.move.update.tx_container_parent = containerParent;
    }
    // let jsonString = JSON.stringify(parameters);

    // console.log({parameters: parameters});

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
    })
    .catch((e) => {
      console.log(e);
    });
  },

  /**
   * returns the next "upper" container colPos parameter inside the code
   * @param element
   * @return int|boolean the colPos
   */
  getColumnPositionForElement: function (element) {
    const columnContainer = element && element.closest('[data-colpos]') ? element.closest('[data-colpos]') : [];
    let result = false;
    if (columnContainer && columnContainer.dataset['colpos'] !== 'undefined') {
      result = (columnContainer.dataset['colpos']) * 1;
    }
    return result;
  },

  /**
   * returns the next "upper" container parent parameter inside the code
   * @param element
   * @return int|boolean the containerParent
   */
  getContainerParentForElement: function (element) {
    const gridContainer = element && element.closest('[data-tx-container-parent]')
      ? element.closest('[data-tx-container-parent]')
      : [];
    let result = false;
    if (gridContainer.length && gridContainer.dataset['txContainerParent'] !== 'undefined') {
      result = gridContainer.dataset['txContainerParent'];
    }
    return result;
  }
}

export default DragDrop;
