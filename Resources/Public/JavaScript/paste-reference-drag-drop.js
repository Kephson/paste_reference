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

import $ from "jquery";
import "jquery-ui/widgets/droppable.js";
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
      start: function () {
        DragDrop.default.onDragStart($(this));
      },
      stop: function () {
        DragDrop.default.onDragStop($(this));
      }
    });
    $(DragDrop.default.dropZoneIdentifier).droppable({
      accept: this.contentIdentifier,
      scope: 'tt_content',
      tolerance: 'pointer',
      over: function (evt, ui) {
        DragDrop.default.onDropHoverOver($(ui.draggable), $(this));
      },
      out: function (evt, ui) {
        DragDrop.default.onDropHoverOut($(ui.draggable), $(this));
      },
      drop: function (evt, ui) {
        DragDrop.default.onDrop($(ui.draggable), $(this), evt);
      }
    });
  },

  /**
   * called when a draggable is selected to be moved
   * @param $element a jQuery object for the draggable
   * @private
   */
  onDragStart: function ($element) {
    // Add css class for the drag shadow
    DragDrop.default.originalStyles = $element.get(0).style.cssText;
    DragDrop.default.drag = $element.children(DragDrop.default.drag);
    DragDrop.default.drag.addClass('dragitem-shadow');
    DragDrop.default.types = $element.find(DragDrop.default.cTypeIdentifier);
    if ($element.closest(DragDrop.default.newContentElementWizardIdentifier).length === 0) {
      $element.append('<div class="ui-draggable-copy-message">' + TYPO3.lang['dragdrop.copy.message'] + '</div>');
    } else {
      // all information about CType, list_type and other default values has to be fetched from onclick
      DragDrop.default.newContentElementOnclick = $element.find('a:first').attr('onclick');
      if (typeof DragDrop.default.newContentElementOnclick !== 'undefined') {
        // this is the relevant part defining the default values for tt_content
        // while creating content with the new element wizard the usual way
        DragDrop.default.newContentElementOnclick = unescape(DragDrop.default.newContentElementOnclick.split('document.editForm.defValues.value=unescape(\'%26')[1].split('\');')[0]);
        if (DragDrop.default.newContentElementOnclick.length) {
          // if there are any default values, they have to be reformatted to an object/array
          // this can be passed on as parameters during the onDrop action after dragging in new content
          // CType is available for each element in the wizard, so this will be the identifier later on
          DragDrop.default.newContentElementDefaultValues = $.parseJSON(
            '{' + DragDrop.default.newContentElementOnclick.replace(/\&/g, '",').replace(/defVals\[tt_content\]\[/g, '"').replace(/\]\=/g, '":"') + '"}'
          );
          DragDrop.default.types.data('ctype', DragDrop.default.newContentElementDefaultValues.CType);
          DragDrop.default.types.data('list_type', DragDrop.default.newContentElementDefaultValues.list_type);
        }
      }
    }
    // Hide create new element button
    DragDrop.default.ownDropZone = $element.children(DragDrop.default.dropZoneIdentifier);
    DragDrop.default.ownDropZone.addClass('drag-start');
    DragDrop.default.column = $element.closest(DragDrop.default.columnIdentifier);
    DragDrop.default.column.removeClass('active');

    $element.parents(DragDrop.default.draggableIdentifier).addClass('move-to-front');
    $element.parents(DragDrop.default.columnHolderIdentifier).find(DragDrop.default.addContentIdentifier).hide();
    $element.find(DragDrop.default.dropZoneIdentifier).hide();

    // make the drop zones visible
    const siblingsDropZones = $element.parents(DragDrop.default.disabledNewContentIdentifier).find(DragDrop.default.dropZoneIdentifier);
    const disabledDropZones = $(DragDrop.default.disabledNewContentIdentifier + ' > ' + DragDrop.default.contentWrapperIdentifier + ' > ' + DragDrop.default.contentIdentifier + ' > ' + DragDrop.default.dropZoneIdentifier);

    $(DragDrop.default.dropZoneIdentifier).not(DragDrop.default.ownDropZone).each(function () {
      $(this).addClass(DragDrop.default.validDropZoneClass);
      if (
        ($(this).not(disabledDropZones).length || siblingsDropZones.length)
        && $(this).parent().find('.icon-actions-add').length
      ) {
        $(this).addClass(DragDrop.default.validDropZoneClass);
      } else {
        $(this).closest(DragDrop.default.contentIdentifier).find('> ' + DragDrop.default.addContentIdentifier + ', > > ' + DragDrop.default.addContentIdentifier).show();
      }
    });
  },


  /**
   * called when a draggable is released
   * @param $element a jQuery object for the draggable
   * @private
   */
  onDragStop: function ($element) {
    // Remove css class for the drag shadow
    DragDrop.default.drag.removeClass('dragitem-shadow');
    // Show create new element button
    DragDrop.default.ownDropZone.removeClass('drag-start');
    DragDrop.default.column.addClass('active');
    $element.parents(DragDrop.default.draggableIdentifier).removeClass('move-to-front');
    $element.parents(DragDrop.default.columnHolderIdentifier).find(DragDrop.default.addContentIdentifier).show();
    $element.find(DragDrop.default.dropZoneIdentifier).show();
    $element.find('.ui-draggable-copy-message').remove();

    // Reset inline style
    $element.get(0).style.cssText = DragDrop.default.originalStyles.replace('z-index: 100;', '');

    $(DragDrop.default.dropZoneIdentifier + '.' + DragDrop.default.validDropZoneClass).removeClass(DragDrop.default.validDropZoneClass);
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
  onDrop: function ($draggableElement, $droppableElement, evt, reference) {
    const newColumn = DragDrop.default.getColumnPositionForElement($droppableElement) ?? 0;
    const containerParent = DragDrop.default.getContainerParentForElement($droppableElement) ?? 0;

    $droppableElement.removeClass(DragDrop.default.dropPossibleHoverClass);
    const $pasteAction = typeof $draggableElement === 'number' || typeof $draggableElement === 'undefined';
    let $pasteElement = null;
    if ($draggableElement) {
      $pasteElement = $draggableElement;
    } else if (typeof top.itemOnClipboardUid === 'number') {
      $pasteElement = top.itemOnClipboardUid;
    }
    // send an AJAX request via the AjaxDataHandler
    let contentElementUid = $pasteAction ? $pasteElement : null;
    if (!contentElementUid && typeof $draggableElement.data === 'function') {
      contentElementUid = parseInt($draggableElement.data('uid') ?? 0);
    }
    if (contentElementUid > 0 || (DragDrop.default.newContentElementDefaultValues.CType && !$pasteAction)) {
      let parameters = {};
      // add the information about a possible column position change
      const targetFound = $droppableElement.closest(DragDrop.default.contentIdentifier).data('uid');
      // the item was moved to the top of the colPos, so the page ID is used here
      let targetPid = 0;
      if (typeof targetFound === 'undefined') {
        // the actual page is needed
        targetPid = $('.t3js-page-ce[data-page]').first().data('page');
      } else {
        // the negative value of the content element after where it should be moved
        targetPid = 0 - parseInt(targetFound);
      }
      const $closestElementWithLanguage = $draggableElement || $droppableElement.closest('[data-language-uid]');
      let language = $closestElementWithLanguage;
      if (language !== parseInt($closestElementWithLanguage)) {
        language = parseInt($closestElementWithLanguage.data('language-uid'));
      }
      if (language !== -1) {
        language = parseInt($droppableElement.closest('[data-language-uid]').data('language-uid'));
      }
      let colPos = 0;
      if (targetPid !== 0) {
        colPos = newColumn;
      }
      parameters['cmd'] = {tt_content: {}};
      parameters['data'] = {tt_content: {}};
      let copyAction = (evt && evt.originalEvent && evt.originalEvent.ctrlKey || $droppableElement.hasClass('t3js-paste-copy') || evt === 'copyFromAnotherPage');
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
            if (!$pasteAction) {
              if (!$droppableElement.parent().hasClass(DragDrop.default.contentIdentifier.substring(1))) {
                $draggableElement.detach().css({top: 0, left: 0})
                .insertAfter($droppableElement.closest(DragDrop.default.dropZoneIdentifier));
              } else {
                $draggableElement.detach().css({top: 0, left: 0})
                .insertAfter($droppableElement.closest(DragDrop.default.contentIdentifier));
              }
            }
            self.location.hash = $droppableElement.closest(DragDrop.default.contentIdentifier).attr('id');
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
        if (containerParent) {
          parameters['cmd']['tt_content'][contentElementUid]['copy']['update']['tx_container_parent'] = containerParent;
        }
        // fire the request, and show a message if it has failed
        // This is adding a copy from another page "to this [selected] place".
        AjaxDataHandler.process(parameters).then(function (result) {
          if (!result.hasErrors) {
            // insert draggable on the new position
            if (!$pasteAction) {
              if (!$droppableElement.parent().hasClass(DragDrop.default.contentIdentifier.substring(1))) {
                $draggableElement.detach().css({top: 0, left: 0})
                .insertAfter($droppableElement.closest(DragDrop.default.dropZoneIdentifier));
              } else {
                $draggableElement.detach().css({top: 0, left: 0})
                .insertAfter($droppableElement.closest(DragDrop.default.contentIdentifier));
              }
            }
            self.location.hash = $droppableElement.closest(DragDrop.default.contentIdentifier).attr('id');
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
        if (containerParent) {
          parameters['cmd']['tt_content'][contentElementUid]['move']['update']['tx_container_parent'] = containerParent;
        }
        // fire the request, and show a message if it has failed
        AjaxDataHandler.process(parameters).then(function (result) {
          if (!result.hasErrors) {
            // insert draggable on the new position
            if (!$pasteAction) {
              if (!$droppableElement.parent().hasClass(DragDrop.default.contentIdentifier.substring(1))) {
                $draggableElement.detach().css({top: 0, left: 0})
                .insertAfter($droppableElement.closest(DragDrop.default.dropZoneIdentifier));
              } else {
                $draggableElement.detach().css({top: 0, left: 0})
                .insertAfter($droppableElement.closest(DragDrop.default.contentIdentifier));
              }
            }
            self.location.hash = $droppableElement.closest(DragDrop.default.contentIdentifier).attr('id');
            self.location.reload();
          }
        });
      }
    }
  },

  /**
   * returns the next "upper" container colPos parameter inside the code
   * @param $element
   * @return int|boolean the colPos
   */
  getColumnPositionForElement: function ($element) {
    const $columnContainer = $element.closest('[data-colpos]');
    if ($columnContainer.length && $columnContainer.data('colpos') !== 'undefined') {
      return $columnContainer.data('colpos');
    } else {
      return false;
    }
  },

  /**
   * returns the next "upper" container parent parameter inside the code
   * @param $element
   * @return int|boolean the containerParent
   */
  getContainerParentForElement: function ($element) {
    const $gridContainer = $element.closest('[data-tx-container-parent]');
    if ($gridContainer.length && $gridContainer.data('txContainerParent') !== 'undefined') {
      return $gridContainer.data('txContainerParent');
    } else {
      return false;
    }
  }
}

export default DragDrop;
