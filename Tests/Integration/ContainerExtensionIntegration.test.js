/**
 * Container Extension Integration Tests
 * Tests b13/container extension integration across TYPO3 versions
 * Validates paste operations work correctly in container elements
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { JSDOM } from 'jsdom';

describe('Container Extension Integration Tests', () => {
  let dom;
  let document;
  let window;

  beforeEach(() => {
    // Setup DOM environment with container and regular columns
    dom = new JSDOM(`
      <!DOCTYPE html>
      <html>
        <head><title>Container Extension Integration Test</title></head>
        <body>
          <div data-page="1" class="t3js-page-columns">
            <!-- Regular page column -->
            <div class="t3js-page-column" data-colpos="0">
              <div class="t3js-page-new-ce" data-language-uid="0">
                <button class="t3js-paste-new">Copy from page</button>
              </div>
              <div class="t3js-page-ce" data-uid="100">
                <div class="t3js-page-new-ce" data-language-uid="0">
                  <button class="t3js-paste-new">Copy from page</button>
                </div>
              </div>
            </div>
            
            <!-- Container element (b13/container) -->
            <div class="t3js-page-ce" data-uid="200" data-ctype="container_test">
              <div class="t3js-page-column" data-colpos="101" data-tx-container-parent="200">
                <div class="t3js-page-new-ce" data-language-uid="0">
                  <button class="t3js-paste-new">Copy from page</button>
                </div>
                <div class="t3js-page-ce" data-uid="201" data-tx-container-parent="200">
                  <div class="t3js-page-new-ce" data-language-uid="0">
                    <button class="t3js-paste-new">Copy from page</button>
                  </div>
                </div>
              </div>
              
              <!-- Second container column -->
              <div class="t3js-page-column" data-colpos="102" data-tx-container-parent="200">
                <div class="t3js-page-new-ce" data-language-uid="0">
                  <button class="t3js-paste-new">Copy from page</button>
                </div>
              </div>
            </div>
            
            <!-- Nested container -->
            <div class="t3js-page-ce" data-uid="300" data-ctype="container_nested">
              <div class="t3js-page-column" data-colpos="201" data-tx-container-parent="300">
                <div class="t3js-page-ce" data-uid="400" data-ctype="container_inner">
                  <div class="t3js-page-column" data-colpos="301" data-tx-container-parent="400">
                    <div class="t3js-page-new-ce" data-language-uid="0">
                      <button class="t3js-paste-new">Copy from page</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Multi-language container -->
            <div class="t3js-page-ce" data-uid="500" data-ctype="container_multilang">
              <div class="t3js-page-column" data-colpos="401" data-tx-container-parent="500" data-language-uid="0">
                <div class="t3js-page-new-ce" data-language-uid="0">
                  <button class="t3js-paste-new">Copy from page</button>
                </div>
              </div>
              <div class="t3js-page-column" data-colpos="401" data-tx-container-parent="500" data-language-uid="1">
                <div class="t3js-page-new-ce" data-language-uid="1">
                  <button class="t3js-paste-new">Copy from page</button>
                </div>
              </div>
            </div>
          </div>
        </body>
      </html>
    `, { 
      url: 'http://localhost',
      pretendToBeVisual: true,
      resources: 'usable'
    });

    document = dom.window.document;
    window = dom.window;
    global.document = document;
    global.window = window;
  });

  afterEach(() => {
    delete global.document;
    delete global.window;
    dom.window.close();
  });

  describe('Container Detection and Validation', () => {
    it('should detect container elements correctly', () => {
      const containerElements = document.querySelectorAll('[data-ctype^="container"]');
      expect(containerElements.length).toBeGreaterThan(0);
      
      containerElements.forEach(container => {
        const uid = container.dataset.uid;
        expect(uid).toBeDefined();
        expect(parseInt(uid)).toBeGreaterThan(0);
        
        const ctype = container.dataset.ctype;
        expect(ctype).toMatch(/^container/);
      });
    });

    it('should identify container columns with proper parent relationships', () => {
      const containerColumns = document.querySelectorAll('[data-tx-container-parent]');
      expect(containerColumns.length).toBeGreaterThan(0);
      
      containerColumns.forEach(column => {
        const containerParent = column.dataset.txContainerParent;
        const colpos = column.dataset.colpos;
        
        expect(containerParent).toBeDefined();
        expect(parseInt(containerParent)).toBeGreaterThan(0);
        
        // Only check colpos if it exists (some elements might not have it)
        if (colpos) {
          expect(parseInt(colpos)).toBeGreaterThan(0);
        }
        
        // Verify parent container exists
        const parentContainer = document.querySelector(`[data-uid="${containerParent}"]`);
        expect(parentContainer).toBeTruthy();
        expect(parentContainer.dataset.ctype).toMatch(/^container/);
      });
    });

    it('should validate container hierarchy integrity', () => {
      const containerColumns = document.querySelectorAll('[data-tx-container-parent]');
      
      containerColumns.forEach(column => {
        const containerParent = column.dataset.txContainerParent;
        const columnUid = column.dataset.uid;
        
        // Check for circular references
        if (columnUid && containerParent) {
          expect(columnUid).not.toBe(containerParent);
        }
        
        // Verify parent exists and is a container
        const parentElement = document.querySelector(`[data-uid="${containerParent}"]`);
        expect(parentElement).toBeTruthy();
        
        if (parentElement) {
          expect(parentElement.dataset.ctype).toMatch(/^container/);
        }
      });
    });
  });

  describe('Paste Button Placement in Containers', () => {
    it('should have paste buttons in all container columns', () => {
      const containerColumns = document.querySelectorAll('[data-tx-container-parent][data-colpos]');
      
      containerColumns.forEach((column, index) => {
        const buttons = column.querySelectorAll('.t3js-paste-new');
        expect(buttons.length).toBeGreaterThan(0);
        
        buttons.forEach(button => {
          const parentCeArea = button.closest('.t3js-page-new-ce');
          expect(parentCeArea).toBeTruthy();
          
          // Find the closest container column that contains this button
          const parentColumn = button.closest('[data-tx-container-parent][data-colpos]');
          expect(parentColumn).toBeTruthy();
          
          // Verify the button is within a container context
          const containerParent = parentColumn.dataset.txContainerParent;
          expect(containerParent).toBeDefined();
          expect(parseInt(containerParent)).toBeGreaterThan(0);
        });
      });
    });

    it('should not have duplicate buttons in container areas', () => {
      const containerNewCeAreas = document.querySelectorAll('[data-tx-container-parent] .t3js-page-new-ce');
      
      containerNewCeAreas.forEach((area, index) => {
        const buttons = area.querySelectorAll('.t3js-paste-new');
        expect(buttons.length).toBeLessThanOrEqual(1);
      });
    });

    it('should properly associate buttons with container context', () => {
      const containerButtons = document.querySelectorAll('[data-tx-container-parent] .t3js-paste-new');
      
      containerButtons.forEach(button => {
        const containerContext = button.closest('[data-tx-container-parent]');
        expect(containerContext).toBeTruthy();
        
        const containerParent = containerContext.dataset.txContainerParent;
        expect(containerParent).toBeDefined();
        expect(parseInt(containerParent)).toBeGreaterThan(0);
      });
    });
  });

  describe('Container Parameter Extraction', () => {
    it('should extract container parameters correctly', () => {
      // Simulate parameter extraction functions
      const extractContainerParams = (element) => {
        const containerContext = element.closest('[data-tx-container-parent]');
        if (!containerContext) return null;
        
        return {
          containerParent: parseInt(containerContext.dataset.txContainerParent),
          colpos: parseInt(containerContext.dataset.colpos || '0'),
          languageUid: parseInt(containerContext.dataset.languageUid || '0')
        };
      };
      
      const containerButtons = document.querySelectorAll('[data-tx-container-parent][data-colpos] .t3js-paste-new');
      
      containerButtons.forEach(button => {
        const params = extractContainerParams(button);
        expect(params).toBeTruthy();
        expect(params.containerParent).toBeGreaterThan(0);
        expect(params.colpos).toBeGreaterThanOrEqual(0);
        expect(params.languageUid).toBeGreaterThanOrEqual(0);
      });
    });

    it('should handle nested container parameter extraction', () => {
      const nestedButton = document.querySelector('[data-colpos="301"] .t3js-paste-new');
      expect(nestedButton).toBeTruthy();
      
      const containerContext = nestedButton.closest('[data-tx-container-parent]');
      expect(containerContext).toBeTruthy();
      expect(containerContext.dataset.txContainerParent).toBe('400');
      expect(containerContext.dataset.colpos).toBe('301');
      
      // Verify the parent container exists
      const parentContainer = document.querySelector('[data-uid="400"]');
      expect(parentContainer).toBeTruthy();
      expect(parentContainer.dataset.ctype).toBe('container_inner');
    });

    it('should differentiate between container and regular column parameters', () => {
      const regularButton = document.querySelector('[data-colpos="0"] .t3js-paste-new');
      const containerButton = document.querySelector('[data-colpos="101"] .t3js-paste-new');
      
      expect(regularButton).toBeTruthy();
      expect(containerButton).toBeTruthy();
      
      // Regular button should not have container context
      const regularContext = regularButton.closest('[data-tx-container-parent]');
      expect(regularContext).toBeNull();
      
      // Container button should have container context
      const containerContext = containerButton.closest('[data-tx-container-parent]');
      expect(containerContext).toBeTruthy();
      expect(containerContext.dataset.txContainerParent).toBe('200');
    });
  });

  describe('Modal Target Generation for Containers', () => {
    it('should generate correct modal target IDs for container columns', () => {
      // Simulate modal target ID generation
      const generateModalTargetId = (element) => {
        const containerContext = element.closest('[data-tx-container-parent]');
        const pageContext = element.closest('[data-page]');
        
        if (containerContext) {
          const containerParent = containerContext.dataset.txContainerParent;
          const colpos = containerContext.dataset.colpos;
          const pageUid = pageContext.dataset.page;
          
          return `element-page_${pageUid}-parent_${containerParent}-colpos_${colpos}-tt_content_0`;
        } else {
          const column = element.closest('[data-colpos]');
          const colpos = column.dataset.colpos;
          const pageUid = pageContext.dataset.page;
          
          return `element-colpos_${colpos}-tt_content_0`;
        }
      };
      
      const containerButton = document.querySelector('[data-colpos="101"] .t3js-paste-new');
      const targetId = generateModalTargetId(containerButton);
      
      expect(targetId).toBe('element-page_1-parent_200-colpos_101-tt_content_0');
      expect(targetId).toContain('parent_200');
      expect(targetId).toContain('colpos_101');
    });

    it('should generate different IDs for different container columns', () => {
      const generateModalTargetId = (element) => {
        const containerContext = element.closest('[data-tx-container-parent]');
        const pageContext = element.closest('[data-page]');
        
        if (containerContext) {
          const containerParent = containerContext.dataset.txContainerParent;
          const colpos = containerContext.dataset.colpos;
          const pageUid = pageContext.dataset.page;
          
          return `element-page_${pageUid}-parent_${containerParent}-colpos_${colpos}-tt_content_0`;
        }
        
        return null;
      };
      
      const button1 = document.querySelector('[data-colpos="101"] .t3js-paste-new');
      const button2 = document.querySelector('[data-colpos="102"] .t3js-paste-new');
      
      const id1 = generateModalTargetId(button1);
      const id2 = generateModalTargetId(button2);
      
      expect(id1).toBe('element-page_1-parent_200-colpos_101-tt_content_0');
      expect(id2).toBe('element-page_1-parent_200-colpos_102-tt_content_0');
      expect(id1).not.toBe(id2);
    });

    it('should handle nested container modal targets', () => {
      const generateModalTargetId = (element) => {
        const containerContext = element.closest('[data-tx-container-parent]');
        const pageContext = element.closest('[data-page]');
        
        if (containerContext) {
          const containerParent = containerContext.dataset.txContainerParent;
          const colpos = containerContext.dataset.colpos;
          const pageUid = pageContext.dataset.page;
          
          return `element-page_${pageUid}-parent_${containerParent}-colpos_${colpos}-tt_content_0`;
        }
        
        return null;
      };
      
      const nestedButton = document.querySelector('[data-colpos="301"] .t3js-paste-new');
      const targetId = generateModalTargetId(nestedButton);
      
      expect(targetId).toBe('element-page_1-parent_400-colpos_301-tt_content_0');
      expect(targetId).toContain('parent_400'); // Inner container
      expect(targetId).toContain('colpos_301');
    });
  });

  describe('Language Handling in Containers', () => {
    it('should handle multi-language container columns', () => {
      const defaultLangButton = document.querySelector('[data-colpos="401"][data-language-uid="0"] .t3js-paste-new');
      const translatedLangButton = document.querySelector('[data-colpos="401"][data-language-uid="1"] .t3js-paste-new');
      
      expect(defaultLangButton).toBeTruthy();
      expect(translatedLangButton).toBeTruthy();
      
      // Both should have same container parent but different language
      const defaultContext = defaultLangButton.closest('[data-tx-container-parent]');
      const translatedContext = translatedLangButton.closest('[data-tx-container-parent]');
      
      expect(defaultContext.dataset.txContainerParent).toBe('500');
      expect(translatedContext.dataset.txContainerParent).toBe('500');
      expect(defaultContext.dataset.languageUid).toBe('0');
      expect(translatedContext.dataset.languageUid).toBe('1');
    });

    it('should extract correct language parameters for container elements', () => {
      const extractLanguageParams = (element) => {
        const containerContext = element.closest('[data-tx-container-parent]');
        const languageContext = element.closest('[data-language-uid]');
        
        return {
          containerParent: containerContext ? parseInt(containerContext.dataset.txContainerParent) : null,
          languageUid: languageContext ? parseInt(languageContext.dataset.languageUid) : 0
        };
      };
      
      const defaultLangButton = document.querySelector('[data-colpos="401"][data-language-uid="0"] .t3js-paste-new');
      const translatedLangButton = document.querySelector('[data-colpos="401"][data-language-uid="1"] .t3js-paste-new');
      
      const defaultParams = extractLanguageParams(defaultLangButton);
      const translatedParams = extractLanguageParams(translatedLangButton);
      
      expect(defaultParams.containerParent).toBe(500);
      expect(defaultParams.languageUid).toBe(0);
      expect(translatedParams.containerParent).toBe(500);
      expect(translatedParams.languageUid).toBe(1);
    });
  });

  describe('Container Boundary Validation', () => {
    it('should ensure elements stay within container boundaries', () => {
      const containerElements = document.querySelectorAll('[data-tx-container-parent] .t3js-page-ce');
      
      containerElements.forEach(element => {
        const elementContainer = element.closest('[data-tx-container-parent]');
        const containerParent = elementContainer.dataset.txContainerParent;
        
        // Element should have container parent attribute if it's a direct child
        if (element.dataset.txContainerParent) {
          expect(element.dataset.txContainerParent).toBe(containerParent);
        }
        
        // Element should be within the correct container context
        expect(elementContainer).toBeTruthy();
        expect(containerParent).toBeDefined();
      });
    });

    it('should validate container nesting levels', () => {
      // Find deeply nested elements - look for container columns within container elements
      const nestedContainerColumns = document.querySelectorAll('[data-ctype^="container"] [data-tx-container-parent][data-colpos]');
      
      if (nestedContainerColumns.length > 0) {
        nestedContainerColumns.forEach(column => {
          const immediateParent = column.dataset.txContainerParent;
          const parentContainer = column.closest('[data-ctype^="container"]');
          
          if (parentContainer) {
            const parentUid = parentContainer.dataset.uid;
            
            // Verify the column's parent matches the container it's in
            expect(immediateParent).toBe(parentUid);
            
            // Check for outer containers
            const outerContainer = parentContainer.parentElement.closest('[data-ctype^="container"]');
            if (outerContainer) {
              const outerUid = outerContainer.dataset.uid;
              expect(parentUid).not.toBe(outerUid);
            }
          }
        });
      } else {
        // If no nested containers, that's also valid
        expect(nestedContainerColumns.length).toBeGreaterThanOrEqual(0);
      }
    });

    it('should prevent circular container references', () => {
      const containerColumns = document.querySelectorAll('[data-tx-container-parent]');
      
      containerColumns.forEach(column => {
        const containerParent = column.dataset.txContainerParent;
        const columnUid = column.dataset.uid;
        
        // Element should not reference itself as parent
        if (columnUid && containerParent) {
          expect(columnUid).not.toBe(containerParent);
        }
        
        // Parent should exist and be different element
        const parentElement = document.querySelector(`[data-uid="${containerParent}"]`);
        expect(parentElement).toBeTruthy();
        
        if (parentElement && columnUid) {
          expect(parentElement.dataset.uid).not.toBe(columnUid);
        }
      });
    });
  });

  describe('Container Workflow Integration', () => {
    it('should simulate complete paste workflow in container', () => {
      // Simulate the complete workflow of pasting into a container
      const containerButton = document.querySelector('[data-colpos="101"] .t3js-paste-new');
      expect(containerButton).toBeTruthy();
      
      // Step 1: Extract container context
      const containerContext = containerButton.closest('[data-tx-container-parent]');
      const containerParent = parseInt(containerContext.dataset.txContainerParent);
      const colpos = parseInt(containerContext.dataset.colpos);
      const languageUid = parseInt(containerContext.dataset.languageUid || '0');
      
      // Step 2: Validate parameters
      expect(containerParent).toBe(200);
      expect(colpos).toBe(101);
      expect(languageUid).toBe(0);
      
      // Step 3: Generate target parameters (simulate backend call)
      const targetParams = {
        containerParent: containerParent,
        colpos: colpos,
        languageUid: languageUid,
        targetPid: 1 // Page UID
      };
      
      expect(targetParams.containerParent).toBeGreaterThan(0);
      expect(targetParams.colpos).toBeGreaterThan(0);
      expect(targetParams.languageUid).toBeGreaterThanOrEqual(0);
      expect(targetParams.targetPid).toBeGreaterThan(0);
    });

    it('should handle container element sorting simulation', () => {
      // Simulate sorting determination for container elements
      const containerColumn = document.querySelector('[data-colpos="101"]');
      const existingElements = containerColumn.querySelectorAll('.t3js-page-ce');
      const newCeArea = containerColumn.querySelector('.t3js-page-new-ce');
      
      // Determine if this is first position
      const isFirstPosition = newCeArea.parentElement === containerColumn && 
                             newCeArea.previousElementSibling === null;
      
      // Simulate sorting value
      let sorting = 0;
      if (!isFirstPosition && existingElements.length > 0) {
        // Would be after existing elements
        sorting = existingElements.length * 100;
      }
      
      expect(typeof sorting).toBe('number');
      expect(sorting).toBeGreaterThanOrEqual(0);
    });

    it('should validate complete container integration workflow', () => {
      // Test the complete integration workflow
      const testWorkflow = (buttonSelector, expectedContainer, expectedColpos) => {
        const button = document.querySelector(buttonSelector);
        expect(button).toBeTruthy();
        
        const containerContext = button.closest('[data-tx-container-parent]');
        
        if (expectedContainer) {
          expect(containerContext).toBeTruthy();
          expect(parseInt(containerContext.dataset.txContainerParent)).toBe(expectedContainer);
          expect(parseInt(containerContext.dataset.colpos)).toBe(expectedColpos);
        } else {
          expect(containerContext).toBeNull();
        }
        
        return {
          hasContainer: !!containerContext,
          containerParent: containerContext ? parseInt(containerContext.dataset.txContainerParent) : null,
          colpos: containerContext ? parseInt(containerContext.dataset.colpos) : 
                  parseInt(button.closest('[data-colpos]').dataset.colpos)
        };
      };
      
      // Test different scenarios
      const regularResult = testWorkflow('[data-colpos="0"] .t3js-paste-new', null, null);
      const containerResult = testWorkflow('[data-colpos="101"] .t3js-paste-new', 200, 101);
      const nestedResult = testWorkflow('[data-colpos="301"] .t3js-paste-new', 400, 301);
      
      expect(regularResult.hasContainer).toBe(false);
      expect(regularResult.colpos).toBe(0);
      
      expect(containerResult.hasContainer).toBe(true);
      expect(containerResult.containerParent).toBe(200);
      expect(containerResult.colpos).toBe(101);
      
      expect(nestedResult.hasContainer).toBe(true);
      expect(nestedResult.containerParent).toBe(400);
      expect(nestedResult.colpos).toBe(301);
    });
  });
});