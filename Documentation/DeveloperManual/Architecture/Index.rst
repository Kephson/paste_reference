.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt

.. _architecture:

============
Architecture
============

This chapter describes how paste_reference integrates into the TYPO3 backend
since version 6.0.0 (TYPO3 14.3) and 4.1.0 (TYPO3 13.4), and why it is built
this way.

.. contents::
   :local:
   :depth: 2


Overview
========

A reference is a regular new content element of type ``shortcut`` that points
to the original record. paste_reference no longer bends the core copy
operation into a reference. It creates the ``shortcut`` element through the
core ``AjaxDataHandler`` and only adds its own controls to the backend.

The extension extends the page module instead of replacing it. The core paste
buttons stay untouched, paste_reference places its own controls next to them
and talks to the core through public core APIs and the markup of the page
module.

.. figure:: /Images/DeveloperManual/architecture.png
   :alt: Architecture of paste_reference: page module, paste toolbar, element
         browser, AjaxDataHandler, PSR-14 listener, shortcut preview and
         DataHandler
   :class: with-shadow

   Components of paste_reference (highlighted) and the core parts they use.


How a reference is created
==========================

Every action ends in a single write path: ``AjaxDataHandler.process()``,
which sends the request to the core route ``record_process``.

*  **Paste as copy** sends the core ``copy`` command.
*  **Paste as reference** sends a datamap that creates a new ``shortcut``
   element.

Example datamap for a reference (comments for explanation only):

.. code-block:: javascript

   {"data": {"tt_content": {"NEW6f3a": {
     "pid": -456,                 // negative: after element 456, positive: top of the column
     "CType": "shortcut",
     "records": "tt_content_123",
     "colPos": 1,
     "sys_language_uid": 0,       // always explicit, see "Language" below
     "tx_container_parent": 789,  // only with EXT:container
     "header": "Reference: Teaser home page"
   }}}}

The DataHandler handles everything else as for any new content element:

*  A negative ``pid`` inserts the record after the given element and takes
   over its page; a positive ``pid`` places it at the top of the column.
*  ``authMode`` for ``CType`` and write access to the target page are checked
   by the DataHandler.
*  In TYPO3 14.3 the column restriction is checked for CType ``shortcut``
   (``DataHandlerContentElementRestrictionHook``), which is the correct rule
   for a reference.
*  In a workspace the record is created as a workspace version of the current
   backend user.

No DataHandler hook and no request parameter are involved, so the behaviour is
the same for every caller.


Components
==========

.. list-table::
   :header-rows: 1
   :widths: 30 70

   *  -  Component
      -  Task

   *  -  ``<paste-reference-toolbar>``
      -  Lit web component, mounted once per "+ Content" area of the page
         module. In copy mode it shows a "Paste" button that opens the
         "Copy / Reference" modal. Independently of the clipboard it shows the
         "From another page" button.

   *  -  ``paste-toolbar.js``
      -  Entry module. Reads the module arguments and mounts the toolbar
         components.

   *  -  ``command.js``
      -  Builds the ``copy`` command or the reference datamap and sends it via
         ``AjaxDataHandler``.

   *  -  ``record-picker.js``
      -  Opens the element browser and receives the selection via the
         ``typo3:elementBrowser:elementAdded`` message. The target position is
         kept in the component, not encoded in field names.

   *  -  ``context-menu-actions.js``
      -  Callback for the context menu entry "Paste as reference"; uses
         ``command.js``.

   *  -  ``PasteReferenceItemProvider``
      -  Context menu item provider. Delivers clipboard uid, target, column,
         language and container parent as data attributes.

   *  -  ``ModifyPageLayoutContentListener``
      -  PSR-14 listener on ``ModifyPageLayoutContentEvent``. Loads the
         toolbar module via ``JavaScriptModuleInstruction`` and passes the
         configuration as module arguments.

   *  -  AJAX route ``paste_reference_record_info``
      -  Returns CType, language and title of a record selected in the
         element browser ("From another page"). Respects workspace overlay,
         page permissions and ``tables_select``; returns 403 or 404 without
         data if access is missing.

   *  -  ``ShortcutPreviewRenderer``
      -  Shows the referenced content elements in the page module.


Behaviour details
=================

Header of the reference
-----------------------

The new element gets the translated header "Reference: {title}" with the
title of the original. The title is a snapshot taken at paste time and is not
updated if the original is renamed later. If the original has no title, the
header falls back to "Reference: [uid]". fluid_styled_content does not render
the header of a ``shortcut`` element in the frontend, so the header is only
visible in the backend.

Language
--------

``sys_language_uid`` is always set explicitly, following the core copy rule:
an original with language ``-1`` (all languages) stays ``-1`` when pasted into
language ``0``. In all other cases the language of the target column applies.

Column restrictions
-------------------

In TYPO3 14.3 the "Reference" option is only offered where ``shortcut`` is
allowed in the target column, and "Copy" only where the CType of the original
is allowed. The DataHandler enforces the same rules on the server. TYPO3 13.4
has no column restrictions in the core, so 4.1.0 always offers "Copy" and
offers "Reference" whenever references are allowed.


Integration points with the TYPO3 core
======================================

Public core APIs used:

*  DataHandler datamap and commands via the route ``record_process``
*  PSR-14 event ``ModifyPageLayoutContentEvent``
*  Context menu item providers with ``data-callback-module``
*  Element browser message ``typo3:elementBrowser:elementAdded``
*  ``previewRenderer`` in TCA
*  Language labels as JavaScript modules ``~labels/paste_reference.db``
   (TYPO3 14.2 and later, Feature #108941)

Dependencies that are **not** guaranteed by the core:

*  The core still describes ``AjaxDataHandler`` as "private API yet".
*  The data attributes of the page module (``data-colpos``,
   ``data-language-uid``, ``data-tx-container-parent``) are stable in
   practice, but not documented as API.
*  Hiding the core paste button relies on the internal class
   ``.t3js-paste-copy``. If the core changes it, the worst case is two paste
   buttons side by side; core paste keeps working.

These are the places that will need attention when the core rebuilds paste and
drag & drop in the page module (Deprecation #109529).


Differences between 6.x and 4.1
===============================

.. list-table::
   :header-rows: 1
   :widths: 25 37 38

   *  -
      -  6.x (TYPO3 14.3)
      -  4.1 (TYPO3 13.4)

   *  -  Branch
      -  ``main``
      -  ``TYPO3_13``

   *  -  Labels in JavaScript
      -  ``~labels/paste_reference.db``, ICU placeholders
      -  Adapter ``labels.js`` on top of ``TYPO3.lang``; the header is built
         on the server

   *  -  Column restriction
      -  Via the core ``column-restriction`` module and DataHandler hook
      -  Not available in the TYPO3 13.4 core

   *  -  Preview renderer
      -  Dependency injection, prepared for TYPO3 v15
      -  As in 4.0 (record as array)

   *  -  User setting
      -  Core API
      -  ``$GLOBALS['TYPO3_USER_SETTINGS']`` in ``ext_tables.php``

The page module markup is identical in both versions, so the JavaScript logic
(toolbar, command, record picker) is shared in substance. Both versions are
maintained in separate branches, because a shared branch would need a version
switch in five places, two of them in exactly the legacy paths 6.0 removes.


Design decisions
================

No DataHandler hook
   Up to 5.x a ``processCmdmap`` hook read ``reference=1`` from the global
   request and re-entered the running DataHandler instance. This did not work
   reliably for CLI, workspace publishing or other AJAX paths and could
   overwrite the state of the running process. A datamap needs no hook at all.

Additive instead of replacing
   5.x hijacked the core paste buttons via ``MutationObserver`` and shipped a
   fork of the core drag & drop module (about 1,660 lines of JavaScript).
   6.0 places its own controls next to the core ones. If the extension fails,
   the core paste function keeps working.

Keep the "Copy / Reference" modal
   Editors keep the familiar dialog. To avoid two paste buttons, the core
   button is hidden via CSS in copy mode.

No workspace cleanup
   The former ``cleanupWorkspacesAfterFinalizing()`` ran after every
   ``tt_content`` command and is no longer needed, because the core creates
   workspace versions correctly.

No drag & drop as reference
   The core drag & drop module allows no third action besides move and copy
   without replacing it. Drag & drop as reference is only possible once the
   core offers an extension point for drop actions.


Known limitations
=================

*  After pasting, the page reloads. Other open backend views are not notified.
*  The header is a snapshot and does not follow later changes of the original.
*  Language label modules are cached by the browser. Clear the browser cache
   after an update if labels look outdated.
*  The toolbar relies on the current page module markup and will need an
   update once the core rework of the page module (#109529) lands.


Outlook
=======

The core is rebuilding paste and drag & drop in the page module (Deprecation
#109529). A core extension point for additional paste actions, for example a
PSR-14 event that adds options to the paste modal, would reduce
paste_reference to the datamap, the label and the preview. A proposal for
such an extension point is planned as a Forge issue.

.. seealso::

   *  `Deprecation #109529: Page module section markup events
      <https://github.com/TYPO3/typo3/blob/v14.3.9/typo3/sysext/core/Documentation/Changelog/14.3/Deprecation-109529-PageModuleSectionMarkupEvents.rst>`__
   *  `Feature #108941: Language labels as JavaScript modules
      <https://github.com/TYPO3/typo3/blob/v14.3.9/typo3/sysext/core/Documentation/Changelog/14.2/Feature-108941-ProvideLanguageLabelsAsVirtualJavaScriptModules.rst>`__
   *  `Breaking #110027: StandardContentPreviewRenderer streamlined
      <https://github.com/TYPO3/typo3/blob/main/typo3/sysext/core/Documentation/Changelog/15.0/Breaking-110027-StandardContentPreviewRendererStreamlined.rst>`__
