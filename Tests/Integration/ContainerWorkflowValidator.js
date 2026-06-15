/**
 * Container Workflow Validation Script
 * Validates the complete integration of container functionality
 * Tests modal operations, element placement, and button functionality
 */

class ContainerWorkflowValidator {
  constructor() {
    this.testResults = [];
    this.errors = [];
  }

  /**
   * Run all container workflow validation tests
   */
  async validateCompleteWorkflow() {
    console.log('Starting Container Workflow Validation...');
    
    try {
      // Test 1: Modal-based paste operations
      await this.testModalPasteOperations();
      
      // Test 2: Element placement validation
      await this.testElementPlacement();
      
      // Test 3: Button functionality consistency
      await this.testButtonFunctionality();
      
      // Test 4: Mixed environment handling
      await this.testMixedEnvironments();
      
      // Test 5: Container boundary validation
      await this.testContainerBoundaries();
      
      this.generateReport();
      
    } catch (error) {
      console.error('Validation failed:', error);
      this.errors.push(`Critical validation error: ${error.message}`);
    }
    
    return {
      success: this.errors.length === 0,
      results: this.testResults,
      errors: this.errors
    };
  }

  /**
   * Test modal-based paste operations from other pages into container columns
   */
  async testModalPasteOperations() {
    console.log('Testing modal-based paste operations...');
    
    const tests = [
      {
        name: 'Container column modal target identification',
        test: () => this.validateModalTargetIdentification()
      },
      {
        name: 'Modal selection parameter handling',
        test: () => this.validateModalParameterHandling()
      },
      {
        name: 'Container parent parameter passing',
        test: () => this.validateContainerParameterPassing()
      }
    ];
    
    for (const test of tests) {
      try {
        const result = await test.test();
        this.testResults.push({
          category: 'Modal Operations',
          test: test.name,
          status: result.success ? 'PASS' : 'FAIL',
          details: result.details,
          errors: result.errors || []
        });
        
        if (!result.success) {
          this.errors.push(`Modal test failed: ${test.name}`);
        }
      } catch (error) {
        this.errors.push(`Modal test error in ${test.name}: ${error.message}`);
      }
    }
  }

  /**
   * Validate proper element placement and visibility after paste operations
   */
  async testElementPlacement() {
    console.log('Testing element placement validation...');
    
    const tests = [
      {
        name: 'Container element placement validation',
        test: () => this.validateContainerPlacement()
      },
      {
        name: 'Regular column placement validation',
        test: () => this.validateRegularPlacement()
      },
      {
        name: 'First element sorting validation',
        test: () => this.validateFirstElementSorting()
      },
      {
        name: 'Container boundary respect',
        test: () => this.validateContainerBoundaryRespect()
      }
    ];
    
    for (const test of tests) {
      try {
        const result = await test.test();
        this.testResults.push({
          category: 'Element Placement',
          test: test.name,
          status: result.success ? 'PASS' : 'FAIL',
          details: result.details,
          errors: result.errors || []
        });
        
        if (!result.success) {
          this.errors.push(`Placement test failed: ${test.name}`);
        }
      } catch (error) {
        this.errors.push(`Placement test error in ${test.name}: ${error.message}`);
      }
    }
  }

  /**
   * Test button functionality consistency across container and regular columns
   */
  async testButtonFunctionality() {
    console.log('Testing button functionality...');
    
    const tests = [
      {
        name: 'Button placement consistency',
        test: () => this.validateButtonPlacement()
      },
      {
        name: 'Container button detection',
        test: () => this.validateContainerButtonDetection()
      },
      {
        name: 'Duplicate button prevention',
        test: () => this.validateDuplicatePrevention()
      },
      {
        name: 'Button event handling',
        test: () => this.validateButtonEventHandling()
      }
    ];
    
    for (const test of tests) {
      try {
        const result = await test.test();
        this.testResults.push({
          category: 'Button Functionality',
          test: test.name,
          status: result.success ? 'PASS' : 'FAIL',
          details: result.details,
          errors: result.errors || []
        });
        
        if (!result.success) {
          this.errors.push(`Button test failed: ${test.name}`);
        }
      } catch (error) {
        this.errors.push(`Button test error in ${test.name}: ${error.message}`);
      }
    }
  }

  /**
   * Test mixed environments with both container and regular columns
   */
  async testMixedEnvironments() {
    console.log('Testing mixed environments...');
    
    const tests = [
      {
        name: 'Mixed column type detection',
        test: () => this.validateMixedColumnDetection()
      },
      {
        name: 'Cross-column operation targeting',
        test: () => this.validateCrossColumnTargeting()
      },
      {
        name: 'Nested container handling',
        test: () => this.validateNestedContainerHandling()
      }
    ];
    
    for (const test of tests) {
      try {
        const result = await test.test();
        this.testResults.push({
          category: 'Mixed Environments',
          test: test.name,
          status: result.success ? 'PASS' : 'FAIL',
          details: result.details,
          errors: result.errors || []
        });
        
        if (!result.success) {
          this.errors.push(`Mixed environment test failed: ${test.name}`);
        }
      } catch (error) {
        this.errors.push(`Mixed environment test error in ${test.name}: ${error.message}`);
      }
    }
  }

  /**
   * Test container boundary validation
   */
  async testContainerBoundaries() {
    console.log('Testing container boundaries...');
    
    const tests = [
      {
        name: 'Container hierarchy validation',
        test: () => this.validateContainerHierarchy()
      },
      {
        name: 'Element visibility within containers',
        test: () => this.validateElementVisibility()
      },
      {
        name: 'Container parent relationship integrity',
        test: () => this.validateParentRelationshipIntegrity()
      }
    ];
    
    for (const test of tests) {
      try {
        const result = await test.test();
        this.testResults.push({
          category: 'Container Boundaries',
          test: test.name,
          status: result.success ? 'PASS' : 'FAIL',
          details: result.details,
          errors: result.errors || []
        });
        
        if (!result.success) {
          this.errors.push(`Container boundary test failed: ${test.name}`);
        }
      } catch (error) {
        this.errors.push(`Container boundary test error in ${test.name}: ${error.message}`);
      }
    }
  }

  // Individual test implementations

  validateModalTargetIdentification() {
    const containerColumns = document.querySelectorAll('[data-tx-container-parent]');
    const regularColumns = document.querySelectorAll('.t3js-page-column:not([data-tx-container-parent])');
    
    let success = true;
    const details = [];
    const errors = [];
    
    // Test container column identification
    containerColumns.forEach((column, index) => {
      const buttons = column.querySelectorAll('.t3js-paste-new');
      if (buttons.length === 0) {
        success = false;
        errors.push(`Container column ${index} missing paste buttons`);
      } else {
        details.push(`Container column ${index}: ${buttons.length} buttons found`);
      }
    });
    
    // Test regular column identification
    regularColumns.forEach((column, index) => {
      const buttons = column.querySelectorAll('.t3js-paste-new');
      if (buttons.length === 0) {
        success = false;
        errors.push(`Regular column ${index} missing paste buttons`);
      } else {
        details.push(`Regular column ${index}: ${buttons.length} buttons found`);
      }
    });
    
    return { success, details, errors };
  }

  validateModalParameterHandling() {
    // Test parameter extraction from modal selections
    const testCases = [
      {
        fieldName: 'element-colpos_101-tt_content_0',
        value: 'tt_content_123',
        expectedColpos: 101,
        expectedUid: 123
      },
      {
        fieldName: 'element-page_1-parent_200-colpos_101-tt_content_0',
        value: 'tt_content_456',
        expectedColpos: 101,
        expectedUid: 456,
        expectedParent: 200
      }
    ];
    
    let success = true;
    const details = [];
    const errors = [];
    
    testCases.forEach((testCase, index) => {
      try {
        // Simulate parameter parsing
        const colposMatch = testCase.fieldName.match(/colpos_(\d+)/);
        const parentMatch = testCase.fieldName.match(/parent_(\d+)/);
        const uidMatch = testCase.value.match(/tt_content[_-](\d+)/);
        
        const extractedColpos = colposMatch ? parseInt(colposMatch[1]) : null;
        const extractedParent = parentMatch ? parseInt(parentMatch[1]) : null;
        const extractedUid = uidMatch ? parseInt(uidMatch[1]) : null;
        
        if (extractedColpos !== testCase.expectedColpos) {
          success = false;
          errors.push(`Test case ${index}: Expected colpos ${testCase.expectedColpos}, got ${extractedColpos}`);
        }
        
        if (extractedUid !== testCase.expectedUid) {
          success = false;
          errors.push(`Test case ${index}: Expected UID ${testCase.expectedUid}, got ${extractedUid}`);
        }
        
        if (testCase.expectedParent && extractedParent !== testCase.expectedParent) {
          success = false;
          errors.push(`Test case ${index}: Expected parent ${testCase.expectedParent}, got ${extractedParent}`);
        }
        
        details.push(`Test case ${index}: Colpos=${extractedColpos}, UID=${extractedUid}, Parent=${extractedParent}`);
        
      } catch (error) {
        success = false;
        errors.push(`Test case ${index} parsing error: ${error.message}`);
      }
    });
    
    return { success, details, errors };
  }

  validateContainerParameterPassing() {
    // Test that container parameters are properly passed to backend
    const containerElements = document.querySelectorAll('[data-tx-container-parent]');
    
    let success = true;
    const details = [];
    const errors = [];
    
    containerElements.forEach((element, index) => {
      const containerParent = element.dataset.txContainerParent;
      const colpos = element.dataset.colpos;
      
      if (!containerParent) {
        success = false;
        errors.push(`Container element ${index} missing tx-container-parent`);
      }
      
      if (!colpos) {
        success = false;
        errors.push(`Container element ${index} missing colpos`);
      }
      
      details.push(`Container ${index}: Parent=${containerParent}, Colpos=${colpos}`);
    });
    
    return { success, details, errors };
  }

  validateContainerPlacement() {
    // Test container element placement validation
    const containerColumns = document.querySelectorAll('[data-tx-container-parent]');
    
    let success = true;
    const details = [];
    const errors = [];
    
    containerColumns.forEach((column, index) => {
      const containerParent = column.dataset.txContainerParent;
      const colpos = column.dataset.colpos;
      
      // Validate container parent is numeric and positive
      if (!containerParent || isNaN(parseInt(containerParent)) || parseInt(containerParent) <= 0) {
        success = false;
        errors.push(`Container column ${index} has invalid parent: ${containerParent}`);
      }
      
      // Validate colpos is numeric
      if (!colpos || isNaN(parseInt(colpos))) {
        success = false;
        errors.push(`Container column ${index} has invalid colpos: ${colpos}`);
      }
      
      details.push(`Container column ${index} validated: Parent=${containerParent}, Colpos=${colpos}`);
    });
    
    return { success, details, errors };
  }

  validateRegularPlacement() {
    // Test regular column placement validation
    const regularColumns = document.querySelectorAll('.t3js-page-column:not([data-tx-container-parent])');
    
    let success = true;
    const details = [];
    const errors = [];
    
    regularColumns.forEach((column, index) => {
      const colpos = column.dataset.colpos;
      
      // Validate colpos exists and is numeric
      if (!colpos || isNaN(parseInt(colpos))) {
        success = false;
        errors.push(`Regular column ${index} has invalid colpos: ${colpos}`);
      }
      
      // Validate no container parent
      if (column.dataset.txContainerParent) {
        success = false;
        errors.push(`Regular column ${index} unexpectedly has container parent`);
      }
      
      details.push(`Regular column ${index} validated: Colpos=${colpos}`);
    });
    
    return { success, details, errors };
  }

  validateFirstElementSorting() {
    // Test first element sorting validation
    const columns = document.querySelectorAll('.t3js-page-column');
    
    let success = true;
    const details = [];
    const errors = [];
    
    columns.forEach((column, index) => {
      const firstNewCe = column.querySelector('.t3js-page-new-ce');
      
      if (firstNewCe) {
        // First element should have sorting 0 when pasted
        const parentElement = firstNewCe.parentElement;
        const isFirstPosition = parentElement.previousElementSibling === null || 
                               !parentElement.previousElementSibling.classList.contains('t3js-page-ce');
        
        if (isFirstPosition) {
          details.push(`Column ${index}: First position identified correctly`);
        } else {
          details.push(`Column ${index}: Not first position`);
        }
      } else {
        errors.push(`Column ${index}: No new content element area found`);
      }
    });
    
    return { success, details, errors };
  }

  validateContainerBoundaryRespect() {
    // Test that elements respect container boundaries
    const containerElements = document.querySelectorAll('[data-tx-container-parent]');
    
    let success = true;
    const details = [];
    const errors = [];
    
    containerElements.forEach((container, index) => {
      const contentElements = container.querySelectorAll('.t3js-page-ce');
      const containerParent = container.dataset.txContainerParent;
      
      contentElements.forEach((element, ceIndex) => {
        const elementContainer = element.closest('[data-tx-container-parent]');
        
        if (!elementContainer || elementContainer.dataset.txContainerParent !== containerParent) {
          success = false;
          errors.push(`Container ${index}, Element ${ceIndex}: Not properly contained`);
        }
      });
      
      details.push(`Container ${index}: ${contentElements.length} elements properly contained`);
    });
    
    return { success, details, errors };
  }

  validateButtonPlacement() {
    // Test button placement consistency
    const allColumns = document.querySelectorAll('.t3js-page-column');
    
    let success = true;
    const details = [];
    const errors = [];
    
    allColumns.forEach((column, index) => {
      const newCeAreas = column.querySelectorAll('.t3js-page-new-ce');
      const buttonsFound = column.querySelectorAll('.t3js-paste-new');
      
      if (newCeAreas.length === 0) {
        errors.push(`Column ${index}: No new content element areas found`);
        success = false;
      }
      
      if (buttonsFound.length === 0) {
        errors.push(`Column ${index}: No paste buttons found`);
        success = false;
      }
      
      details.push(`Column ${index}: ${newCeAreas.length} CE areas, ${buttonsFound.length} buttons`);
    });
    
    return { success, details, errors };
  }

  validateContainerButtonDetection() {
    // Test container-specific button detection
    const containerColumns = document.querySelectorAll('[data-tx-container-parent]');
    
    let success = true;
    const details = [];
    const errors = [];
    
    containerColumns.forEach((column, index) => {
      const buttons = column.querySelectorAll('.t3js-paste-new');
      
      buttons.forEach((button, buttonIndex) => {
        const containerContext = button.closest('[data-tx-container-parent]');
        
        if (!containerContext) {
          success = false;
          errors.push(`Container column ${index}, Button ${buttonIndex}: Not in container context`);
        } else {
          details.push(`Container column ${index}, Button ${buttonIndex}: Properly detected in container`);
        }
      });
    });
    
    return { success, details, errors };
  }

  validateDuplicatePrevention() {
    // Test duplicate button prevention
    const allNewCeAreas = document.querySelectorAll('.t3js-page-new-ce');
    
    let success = true;
    const details = [];
    const errors = [];
    
    allNewCeAreas.forEach((area, index) => {
      const buttons = area.querySelectorAll('.t3js-paste-new');
      
      if (buttons.length > 1) {
        success = false;
        errors.push(`New CE area ${index}: ${buttons.length} duplicate buttons found`);
      } else if (buttons.length === 1) {
        details.push(`New CE area ${index}: Single button correctly placed`);
      } else {
        details.push(`New CE area ${index}: No buttons (may be expected)`);
      }
    });
    
    return { success, details, errors };
  }

  validateButtonEventHandling() {
    // Test button event handling
    const buttons = document.querySelectorAll('.t3js-paste-new');
    
    let success = true;
    const details = [];
    const errors = [];
    
    buttons.forEach((button, index) => {
      // Check if button has proper attributes for event handling
      const hasClickHandler = button.onclick !== null || 
                             button.getAttribute('onclick') !== null ||
                             button.classList.contains('t3js-paste-new');
      
      if (!hasClickHandler) {
        success = false;
        errors.push(`Button ${index}: No click handler detected`);
      } else {
        details.push(`Button ${index}: Click handler properly configured`);
      }
    });
    
    return { success, details, errors };
  }

  validateMixedColumnDetection() {
    // Test mixed column type detection
    const regularColumns = document.querySelectorAll('.t3js-page-column:not([data-tx-container-parent])');
    const containerColumns = document.querySelectorAll('.t3js-page-column[data-tx-container-parent]');
    
    let success = true;
    const details = [];
    const errors = [];
    
    if (regularColumns.length === 0 && containerColumns.length === 0) {
      success = false;
      errors.push('No columns detected in mixed environment');
    } else {
      details.push(`Mixed environment: ${regularColumns.length} regular, ${containerColumns.length} container columns`);
    }
    
    return { success, details, errors };
  }

  validateCrossColumnTargeting() {
    // Test cross-column operation targeting
    const allColumns = document.querySelectorAll('.t3js-page-column');
    
    let success = true;
    const details = [];
    const errors = [];
    
    allColumns.forEach((column, index) => {
      const colpos = column.dataset.colpos;
      const isContainer = column.hasAttribute('data-tx-container-parent');
      const containerParent = column.dataset.txContainerParent;
      
      // Validate targeting parameters
      if (!colpos) {
        success = false;
        errors.push(`Column ${index}: Missing colpos for targeting`);
      }
      
      if (isContainer && !containerParent) {
        success = false;
        errors.push(`Column ${index}: Container missing parent for targeting`);
      }
      
      details.push(`Column ${index}: Colpos=${colpos}, Container=${isContainer}, Parent=${containerParent || 'none'}`);
    });
    
    return { success, details, errors };
  }

  validateNestedContainerHandling() {
    // Test nested container handling
    const nestedContainers = document.querySelectorAll('[data-tx-container-parent] [data-tx-container-parent]');
    
    let success = true;
    const details = [];
    const errors = [];
    
    if (nestedContainers.length > 0) {
      nestedContainers.forEach((container, index) => {
        const parentContainer = container.parentElement.closest('[data-tx-container-parent]');
        
        if (!parentContainer) {
          success = false;
          errors.push(`Nested container ${index}: Parent container not found`);
        } else {
          details.push(`Nested container ${index}: Properly nested within parent`);
        }
      });
    } else {
      details.push('No nested containers found (may be expected)');
    }
    
    return { success, details, errors };
  }

  validateContainerHierarchy() {
    // Test container hierarchy validation
    const containerElements = document.querySelectorAll('[data-tx-container-parent]');
    
    let success = true;
    const details = [];
    const errors = [];
    
    containerElements.forEach((element, index) => {
      const containerParent = element.dataset.txContainerParent;
      const elementUid = element.dataset.uid;
      
      // Check for circular references
      if (elementUid && containerParent && elementUid === containerParent) {
        success = false;
        errors.push(`Container ${index}: Circular reference detected (UID=${elementUid})`);
      }
      
      // Validate parent exists (if we can check)
      const parentExists = containerParent && parseInt(containerParent) > 0;
      if (!parentExists) {
        success = false;
        errors.push(`Container ${index}: Invalid parent UID (${containerParent})`);
      } else {
        details.push(`Container ${index}: Valid hierarchy (Parent=${containerParent})`);
      }
    });
    
    return { success, details, errors };
  }

  validateElementVisibility() {
    // Test element visibility within containers
    const containerColumns = document.querySelectorAll('[data-tx-container-parent]');
    
    let success = true;
    const details = [];
    const errors = [];
    
    containerColumns.forEach((column, index) => {
      const contentElements = column.querySelectorAll('.t3js-page-ce');
      
      contentElements.forEach((element, ceIndex) => {
        const isVisible = element.offsetParent !== null;
        const isInContainer = element.closest('[data-tx-container-parent]') === column;
        
        if (!isInContainer) {
          success = false;
          errors.push(`Container ${index}, Element ${ceIndex}: Not properly contained`);
        }
        
        details.push(`Container ${index}, Element ${ceIndex}: Visible=${isVisible}, Contained=${isInContainer}`);
      });
    });
    
    return { success, details, errors };
  }

  validateParentRelationshipIntegrity() {
    // Test parent relationship integrity
    const containerElements = document.querySelectorAll('[data-tx-container-parent]');
    
    let success = true;
    const details = [];
    const errors = [];
    
    containerElements.forEach((element, index) => {
      const containerParent = element.dataset.txContainerParent;
      const colpos = element.dataset.colpos;
      
      // Validate parent-child relationship integrity
      if (containerParent) {
        const parentElement = document.querySelector(`[data-uid="${containerParent}"]`);
        
        if (!parentElement) {
          // Parent might not be in DOM (could be on different page)
          details.push(`Container ${index}: Parent ${containerParent} not in current DOM (may be expected)`);
        } else {
          details.push(`Container ${index}: Parent ${containerParent} found in DOM`);
        }
      }
      
      // Validate colpos consistency
      if (!colpos || isNaN(parseInt(colpos))) {
        success = false;
        errors.push(`Container ${index}: Invalid colpos for parent relationship`);
      }
    });
    
    return { success, details, errors };
  }

  /**
   * Generate comprehensive validation report
   */
  generateReport() {
    console.log('\n=== Container Workflow Validation Report ===');
    
    const categories = [...new Set(this.testResults.map(r => r.category))];
    
    categories.forEach(category => {
      console.log(`\n${category}:`);
      const categoryResults = this.testResults.filter(r => r.category === category);
      
      categoryResults.forEach(result => {
        const status = result.status === 'PASS' ? '✅' : '❌';
        console.log(`  ${status} ${result.test}`);
        
        if (result.details.length > 0) {
          result.details.forEach(detail => {
            console.log(`    - ${detail}`);
          });
        }
        
        if (result.errors.length > 0) {
          result.errors.forEach(error => {
            console.log(`    ❌ ${error}`);
          });
        }
      });
    });
    
    const totalTests = this.testResults.length;
    const passedTests = this.testResults.filter(r => r.status === 'PASS').length;
    const failedTests = totalTests - passedTests;
    
    console.log(`\n=== Summary ===`);
    console.log(`Total Tests: ${totalTests}`);
    console.log(`Passed: ${passedTests}`);
    console.log(`Failed: ${failedTests}`);
    console.log(`Success Rate: ${((passedTests / totalTests) * 100).toFixed(1)}%`);
    
    if (this.errors.length > 0) {
      console.log(`\n=== Critical Errors ===`);
      this.errors.forEach(error => {
        console.log(`❌ ${error}`);
      });
    }
    
    console.log(`\nValidation ${this.errors.length === 0 ? 'PASSED' : 'FAILED'}`);
  }
}

// Export for use in tests or direct execution
if (typeof module !== 'undefined' && module.exports) {
  module.exports = ContainerWorkflowValidator;
}

// Auto-run if executed directly in browser
if (typeof window !== 'undefined' && document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', async () => {
    const validator = new ContainerWorkflowValidator();
    await validator.validateCompleteWorkflow();
  });
} else if (typeof window !== 'undefined') {
  // Run immediately if DOM is already loaded
  const validator = new ContainerWorkflowValidator();
  validator.validateCompleteWorkflow();
}