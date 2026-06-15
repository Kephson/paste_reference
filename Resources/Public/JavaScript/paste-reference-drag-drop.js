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
 */
import AjaxDataHandler from "@typo3/backend/ajax-data-handler.js";
import BroadcastService from '@typo3/backend/broadcast-service.js';
import { BroadcastMessage } from '@typo3/backend/broadcast-message.js';
import DragDrop from "@typo3/backend/layout-module/drag-drop.js";
import Draggable from "@ehaerer/paste-reference/paste-reference-draggable.js";
// import Paste from "@typo3/backend/layout-module/paste.js";

'use strict';

/**
 * Module: @ehaerer/paste-reference/paste-reference-drag-drop.js
 * @exports @ehaerer/paste-reference/paste-reference-drag-drop.js
 */
DragDrop.default = {
  contentIdentifier: '.t3js-page-ce',
  draggableIdentifier: '.t3js-page-ce:has(.t3-page-ce-header-draggable)',
  droppableElement: '',
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
  initialize: function () {
    (DragDrop.default.draggableIdentifier).draggable({
      handle: this.draggableContentHandle,
      scope: 'tt_content',
      cursor: 'move',
      distance: 20,
      addClasses: 'active-drag',
      revert: 'invalid',
    });
    (DragDrop.default.dropZoneIdentifier).droppable({
      accept: this.draggableContentHandle,
      scope: 'tt_content',
      tolerance: 'pointer',
    });
  },

  parseDraggableParameter: function (draggableParameter, event) {
    let colPos = null;
    let draggableElement = null;
    if ((draggableParameter + " ").indexOf('|') !== -1) {
      colPos = (draggableParameter + " ").split('|')[0];
      draggableElement = (" " + draggableParameter).split('|')[1];
    } else {
      draggableElement = draggableParameter;
    }
    // console.log(JSON.stringify({
    //   colPos: colPos,
    //   draggableElement: draggableElement,
    //   draggableParameter: draggableParameter
    // }));
    return {
      colPos: parseInt(colPos),
      draggableElement: draggableElement
    };
  },
   */

  /**
   * this method does the whole logic when a draggable is dropped
   * on to a dropzone, sending out the request and afterwards move
   * the HTML element in the right place.
   *
   * @param draggableParameter
   * @param droppableElement
   * @param {Event} event the event
   * @param reference if content should be pasted as copy or reference
   * @private
   */
  onDrop: function (
    draggableParameter,
    droppableElement,
    itemUid,
    event,
    reference
  ) {

    /*
    console.log({
      'draggableParameter': draggableParameter,
      'droppableElement': droppableElement,
      'itemUid': itemUid,
      'event': event,
      'reference': reference,
    });
    console.trace(draggableParameter);
    */

    let draggable;
    let draggableElement = null;
    let pasteElement = null;

// console.log('draggableParameter instanceof Draggable', draggableParameter instanceof Draggable, draggableParameter);
// console.trace(itemUid);

    // Check if first parameter is a Draggable object
    if (draggableParameter
      && typeof draggableParameter === 'object'
      && draggableParameter instanceof Draggable
    ) {
      draggable = draggableParameter;
      pasteElement = draggable.getUid();
      draggableElement = pasteElement;
    } else {
      throw new Error('Draggable Object not propoerly transferred. ' + JSON.stringify(draggableParameter));
    }

    // Extract positioning data from Draggable object
    const colPos = draggable.getColpos();
    const txContainerParent = draggable.getTxContainerParent();
    const languageUid = draggable.getLanguageUid();
    let sorting = null;
    if (draggable.getSorting() !== null) {
      sorting = parseInt(draggable.getSorting());
    }

    const pasteAction = typeof draggableElement === 'number' || typeof draggableElement === 'undefined';
    let contentElementUid = pasteAction ? pasteElement : null;

    if (!contentElementUid && draggableElement && draggableElement.dataset) {
      contentElementUid = parseInt(draggableElement.dataset['uid'] ?? 0);
    }

    droppableElement.classList.remove(DragDrop.default.dropPossibleHoverClass);

    // send an AJAX request via the AjaxDataHandler
    if (contentElementUid > 0 || !pasteAction) {
      // add the information about a possible column position change
      const foundTarget = droppableElement.closest(DragDrop.default.contentIdentifier)?.dataset['uid'];
      // the item was moved to the top of the colPos, so the page ID is used here
      let targetPid = 0;

      if (typeof foundTarget === 'undefined') {
        targetPid = draggable.getPid();
        sorting = 0;
        /*
        console.log('Setting sorting=0 for first element in container column:', {
          colPos: colPos,
          txContainerParent: txContainerParent,
        });
        */
      } else {
        // the negative value of the content element after where it should be moved
        targetPid = 0 - parseInt(foundTarget);
      }

      /*
      console.log({
        'foundTarget': foundTarget,
        'targetPid': targetPid,
        'contentElementUid': contentElementUid,
        // 'containerContext': containerContext,
        'colPos': colPos,
        'txContainerParent': txContainerParent,
        'languageUid': languageUid
      });
      */

      let task = {
        'new': false,
        'browse': (
            reference
            && reference === 'copyFromAnotherPage'
        ),
        'isCopyAction': (
            event && (
              (event.originalEvent && event.originalEvent.ctrlKey)
            )
            || droppableElement.classList.contains('t3js-paste-copy')
        )
      };

      const args = {
        draggableElement: draggableElement,
        droppableElement: droppableElement,
        pasteAction: pasteAction,
        targetPid: targetPid,
        colPos: colPos,
        languageUid: languageUid,
        txContainerParent: txContainerParent,
        // containerContext: containerContext,
        event: event,
        reference: reference,
        isCopyAction: (task.isCopyAction ?? false)
      }

      // Add sorting if determined from container context
      if (sorting !== null) {
        args.sorting = sorting;
      }

      /*
      console.log('Drop operation args:', args);
console.log(draggable);
*/
      if (task.new) {
        // console.log('newAction');
        this.newAction(args);
      } else if (task.browse) {
        // console.log('processModalSelection');
        this.processModalSelection(args);
      } else {
        // console.log('processClipboardSelection');
        this.processClipboardSelection(args);
      }
    }
  },

  newAction: function (args) {
    let generalParameters = {
      data: {
        tt_content: {
          "NEW234134": DragDrop.default.newContentElementDefaultValues,
        },
      },
      cmd: { tt_content: {} },
    };
    let defaultParameters = {
      data: {
        tt_content: {
          "NEW234134": {
            pid: args.targetPid,
            colPos: args.colPos,
            sys_language_uid: args.languageUid,
            sorting: 0,
          }
        },
      },
    }
    let parameters = generalParameters.concat(defaultParameters);
    if (!parameters.data.tt_content.NEW234134.header) {
      parameters.data.tt_content.NEW234134.header = TYPO3.l10n.localize('tx_paste_reference_js.newcontentelementheader');
    }
    parameters.basicAction = 'new';

    this.ajaxAction(parameters, args.draggableElement, args.droppableElement, args.pasteAction, 'newAction');
  },

  /**
   * Process modal selection with proper container parent parameters from Draggable context
   */
  processModalSelection: function (args) {

    // till now, selecting CEs from the modal produce always copies
    // idea: with a good visual interface moves and / or paste-reference could theoretically be integrated
    const datahandlerCommand = 'copy'; // args.isCopyAction ? 'copy' : 'move';

    const parameters = {
      cmd: {
        tt_content: {
          [args.draggableElement]: {
            [datahandlerCommand]: {
              action: 'paste',
              target: args.targetPid,
              update: {
                colPos: args.colPos,
                sys_language_uid: args.languageUid,
              },
            }
          }
        }
      },
      basicAction: datahandlerCommand
    };

    // Handle container parent parameters using Draggable context
    if (args.txContainerParent && args.txContainerParent > 0) {
      parameters.cmd.tt_content[args.draggableElement][datahandlerCommand].update.tx_container_parent = args.txContainerParent;
    }

    if(args.sorting === 0) {
      parameters.cmd.tt_content[args.draggableElement][datahandlerCommand].update.sorting = "0";
    }

    if (args.reference === 'copyFromAnotherPage') {
      parameters['CB'] = { setCopyMode: 1 };
    }

    // console.log('Modal selection parameters:', parameters);
    this.ajaxAction(parameters, args.draggableElement, args.droppableElement, args.pasteAction, 'processModalSelection');
  },

  /**
   * Process clipboard selection using Draggable-generated container information
   */
  processClipboardSelection: function (args) {

    const datahandlerCommand = args.isCopyAction ? 'copy' : 'move';

    const parameters = {
      CB: {
        paste: 'tt_content|' + args.targetPid,
        pad: 'normal',
        update: {
          colPos: args.colPos,
          sys_language_uid: args.languageUid,
        },
      },
    };

    // Handle reference parameter
    if (args.reference === 'reference') {
      parameters.reference = 1;
    }

    // Handle container parent using Draggable-generated information
    if (args.txContainerParent && args.txContainerParent > 0) {
      parameters.CB.update.tx_container_parent = args.txContainerParent;
    }

    if (args.sorting !== undefined) {
      parameters.CB.update.sorting = args.sorting;
    }

    /*
    console.log('Clipboard selection parameters:', {
      args: args,
      parameters: parameters,
      txContainerParent: args.txContainerParent,
      // containerContext: args.containerContext,
      droppableElement: args.droppableElement
    });
    */

    this.ajaxAction(parameters, args.draggableElement, args.droppableElement, args.pasteAction, 'processClipboardSelection', args.basicAction);
  },

  ajaxAction: function (
    parameters,
    draggableElement,
    droppableElement,
    pasteAction,
    action,
    basicAction
  ) {
    const thisClass = this;
    // console.log(JSON.stringify({ parameters: parameters, action: action }));


    // fire the request, and show a message if it has failed
    // This is adding a copy from another page "to this [selected] place".
    AjaxDataHandler.process(parameters).then(function (result) {
      if (!result.hasErrors) {
        // insert draggable on the new position
        /*
        console.log('result, pasteAction:', pasteAction);
        console.log(
          'contentIdentifier',
          DragDrop.default.contentIdentifier,
          DragDrop.default.contentIdentifier.substring(1),
          'droppableElement',
          DragDrop.default.droppableElement,
          DragDrop.default.droppableElement.substring(1)
        );
        */
        if (!pasteAction) {
          /*
          console.log(
            DragDrop.default.contentIdentifier,
            DragDrop.default.contentIdentifier.substring(1),
            DragDrop.default.droppableElement.substring(1)
          );
          */

          if (!droppableElement.parent().hasClass(
            DragDrop.default.contentIdentifier.substring(1)
          )) {
            draggableElement.remove();
            draggableElement.style.top = '0';
            draggableElement.style.left = '0';
            const targetElement = droppableElement.closest(DragDrop.default.droppableElement);
            // Insert after the target element (equivalent to insertAfter())
            if (targetElement && targetElement.parentElement) {
              targetElement.parentElement.insertBefore(draggableElement, targetElement.nextSibling);
            }
          } else {
            draggableElement.remove();
            draggableElement.style.top = '0';
            draggableElement.style.left = '0';
            // Insert after the target element (equivalent to insertAfter())
            const targetElement = droppableElement.closest(DragDrop.default.contentIdentifier);
            if (targetElement && targetElement.parentElement) {
              targetElement.parentElement.insertBefore(draggableElement, targetElement.nextSibling);
            }
          }
        }
        /*
        if (parameters.basicAction == 'copy' || parameters.basicAction == 'move') {
          thisClass.broadcast('elementChanged', {
            pid: draggableElement.pid,
            uid: (typeof draggableElement === 'number' ? draggableElement : draggableElement.uid),
            targetPid: thisClass.getCurrentPageId(),
            action: parameters.basicAction
          });
        }
        */
        self.location.hash = droppableElement.closest(DragDrop.default.contentIdentifier).id;
        /*
        console.log(parameters.basicAction,{
              pid: draggableElement.pid,
              uid: (typeof draggableElement === 'number' ? draggableElement : draggableElement.uid),
              targetPid: thisClass.getCurrentPageId(),
              action: parameters.basicAction,
              hash: droppableElement.closest(DragDrop.default.contentIdentifier).id
          });
        */
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
    let result = false;
    try {
      const columnContainer = element && element.closest('[data-colpos]') ? element.closest('[data-colpos]') : [];
      if (columnContainer && columnContainer.dataset['colpos'] !== 'undefined') {
        result = (columnContainer.dataset['colpos']) * 1;
      }
    } catch(e) {
      console.log(e.message);
    };
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
    if (gridContainer.dataset && gridContainer.dataset['txContainerParent'] !== 'undefined') {
      result = gridContainer.dataset['txContainerParent'];
    }
    if (Object.prototype.toString.call(element) == '[object HTMLDivElement]') {
      // console.log('getContainerParentForElement', Object.prototype.toString.call(element),element, result);
    }
    return result;
  },

  broadcast: function (eventName, payload) {
    BroadcastService.post(new BroadcastMessage('page-layout-drag-drop', eventName, payload || {}));
  },
  getCurrentPageId: function () {
    return parseInt(document.querySelector('[data-page]').dataset.page, 10);
  }
}

export default DragDrop;
