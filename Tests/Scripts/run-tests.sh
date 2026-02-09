#!/bin/bash

# TYPO3 Multi-Version Test Runner
# This script orchestrates the complete test setup and execution

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to run complete environment setup
setup_complete_environment() {
    local version=${1:-"all"}
    
    log_info "Setting up complete TYPO3 test environment..."
    
    # Step 1: Setup Docker environments
    log_info "Step 1: Setting up Docker environments..."
    "$SCRIPT_DIR/setup-typo3-environment.sh" setup "$version"
    
    # Step 2: Install extensions
    log_info "Step 2: Installing extensions..."
    sleep 10  # Wait for environments to be fully ready
    "$SCRIPT_DIR/install-extension.sh" install "$version"
    
    # Step 3: Seed test data
    log_info "Step 3: Seeding test data..."
    "$SCRIPT_DIR/seed-test-data.sh" seed "$version"
    
    log_success "Complete environment setup finished for version(s): $version"
}

# Function to run tests
run_tests() {
    local version=${1:-"all"}
    local test_type=${2:-"all"}
    
    log_info "Running tests for TYPO3 version(s): $version, type: $test_type"
    
    # Check if environments are running
    if [ "$version" = "all" ] || [ "$version" = "13" ]; then
        if ! docker ps | grep -q "typo3-v13_web_1"; then
            log_error "TYPO3 v13 environment is not running. Please run setup first."
            return 1
        fi
    fi
    
    if [ "$version" = "all" ] || [ "$version" = "14" ]; then
        if ! docker ps | grep -q "typo3-v14_web_1"; then
            log_error "TYPO3 v14 environment is not running. Please run setup first."
            return 1
        fi
    fi
    
    # Run PHPUnit tests
    if [ "$test_type" = "all" ] || [ "$test_type" = "unit" ] || [ "$test_type" = "php" ]; then
        run_phpunit_tests "$version"
    fi
    
    # Run JavaScript tests
    if [ "$test_type" = "all" ] || [ "$test_type" = "js" ] || [ "$test_type" = "javascript" ]; then
        run_javascript_tests "$version"
    fi
    
    # Run container extension tests
    if [ "$test_type" = "all" ] || [ "$test_type" = "container" ]; then
        run_container_tests "$version"
    fi
    
    # Run integration tests
    if [ "$test_type" = "all" ] || [ "$test_type" = "integration" ]; then
        run_integration_tests "$version"
    fi
}

# Function to run PHPUnit tests
run_phpunit_tests() {
    local version=${1:-"all"}
    
    log_info "Running PHPUnit tests..."
    
    if [ "$version" = "all" ] || [ "$version" = "13" ]; then
        log_info "Running PHPUnit tests for TYPO3 v13..."
        docker exec "typo3-v13_web_1" php vendor/bin/phpunit \
            --configuration /var/www/html/extensions/paste_reference/Tests/phpunit.xml \
            --testsuite unit \
            --colors=always || log_warning "Some PHPUnit tests failed for TYPO3 v13"
    fi
    
    if [ "$version" = "all" ] || [ "$version" = "14" ]; then
        log_info "Running PHPUnit tests for TYPO3 v14..."
        docker exec "typo3-v14_web_1" php vendor/bin/phpunit \
            --configuration /var/www/html/extensions/paste_reference/Tests/phpunit.xml \
            --testsuite unit \
            --colors=always || log_warning "Some PHPUnit tests failed for TYPO3 v14"
    fi
}

# Function to run JavaScript tests
run_javascript_tests() {
    local version=${1:-"all"}
    
    log_info "Running JavaScript tests..."
    
    # Run tests in project root (they will test against both environments)
    cd "$PROJECT_ROOT"
    
    if command -v npm &> /dev/null; then
        log_info "Running JavaScript tests with npm..."
        npm test || log_warning "Some JavaScript tests failed"
    elif command -v yarn &> /dev/null; then
        log_info "Running JavaScript tests with yarn..."
        yarn test || log_warning "Some JavaScript tests failed"
    else
        log_warning "Neither npm nor yarn found, skipping JavaScript tests"
    fi
}

# Function to run container extension tests
run_container_tests() {
    local version=${1:-"all"}
    
    log_info "Running container extension compatibility tests..."
    
    if [ "$version" = "all" ] || [ "$version" = "13" ]; then
        log_info "Running container tests for TYPO3 v13..."
        docker exec "typo3-v13_web_1" php vendor/bin/phpunit \
            --configuration /var/www/html/extensions/paste_reference/Tests/phpunit.xml \
            --testsuite functional \
            --filter ContainerExtensionCompatibilityTest \
            --colors=always || log_warning "Some container tests failed for TYPO3 v13"
    fi
    
    if [ "$version" = "all" ] || [ "$version" = "14" ]; then
        log_info "Running container tests for TYPO3 v14..."
        docker exec "typo3-v14_web_1" php vendor/bin/phpunit \
            --configuration /var/www/html/extensions/paste_reference/Tests/phpunit.xml \
            --testsuite functional \
            --filter ContainerExtensionCompatibilityTest \
            --colors=always || log_warning "Some container tests failed for TYPO3 v14"
    fi
}

# Function to run integration tests
run_integration_tests() {
    local version=${1:-"all"}
    
    log_info "Running integration tests..."
    
    if [ "$version" = "all" ] || [ "$version" = "13" ]; then
        log_info "Running integration tests for TYPO3 v13..."
        docker exec "typo3-v13_web_1" php vendor/bin/phpunit \
            --configuration /var/www/html/extensions/paste_reference/Tests/phpunit.xml \
            --testsuite integration \
            --colors=always || log_warning "Some integration tests failed for TYPO3 v13"
    fi
    
    if [ "$version" = "all" ] || [ "$version" = "14" ]; then
        log_info "Running integration tests for TYPO3 v14..."
        docker exec "typo3-v14_web_1" php vendor/bin/phpunit \
            --configuration /var/www/html/extensions/paste_reference/Tests/phpunit.xml \
            --testsuite integration \
            --colors=always || log_warning "Some integration tests failed for TYPO3 v14"
    fi
}

# Function to show test results summary
show_test_summary() {
    log_info "Test Results Summary:"
    echo "====================="
    
    # Check if test result files exist and show summary
    for version in 13 14; do
        local log_file="/tmp/typo3-v${version}-test-results.log"
        if [ -f "$log_file" ]; then
            log_info "TYPO3 v$version Results:"
            cat "$log_file"
        fi
    done
}

# Function to cleanup test environments
cleanup_environments() {
    local version=${1:-"all"}
    
    log_warning "Cleaning up test environments..."
    
    # Stop and remove containers
    "$SCRIPT_DIR/setup-typo3-environment.sh" clean "$version"
    
    # Clean test data
    "$SCRIPT_DIR/seed-test-data.sh" clean "$version"
    
    log_success "Cleanup completed"
}

# Function to show environment status
show_status() {
    log_info "TYPO3 Test Environment Status:"
    echo "=============================="
    
    "$SCRIPT_DIR/setup-typo3-environment.sh" status
    
    # Show additional information
    echo ""
    log_info "Available Commands:"
    echo "  Backend Access:"
    echo "    TYPO3 v13: http://localhost:8013/typo3 (admin/password)"
    echo "    TYPO3 v14: http://localhost:8014/typo3 (admin/password)"
    echo ""
    echo "  Database Access:"
    echo "    TYPO3 v13: localhost:3313 (typo3/typo3)"
    echo "    TYPO3 v14: localhost:3314 (typo3/typo3)"
    echo ""
    echo "  Test Users:"
    echo "    test_editor/password (Editor)"
    echo "    test_admin/password (Admin)"
}

# Function to run quick health check
health_check() {
    local version=${1:-"all"}
    
    log_info "Running health check..."
    
    local all_healthy=true
    
    if [ "$version" = "all" ] || [ "$version" = "13" ]; then
        if curl -f -s "http://localhost:8013" > /dev/null; then
            log_success "TYPO3 v13 web server: OK"
        else
            log_error "TYPO3 v13 web server: FAILED"
            all_healthy=false
        fi
        
        if curl -f -s "http://localhost:8013/typo3" > /dev/null; then
            log_success "TYPO3 v13 backend: OK"
        else
            log_error "TYPO3 v13 backend: FAILED"
            all_healthy=false
        fi
    fi
    
    if [ "$version" = "all" ] || [ "$version" = "14" ]; then
        if curl -f -s "http://localhost:8014" > /dev/null; then
            log_success "TYPO3 v14 web server: OK"
        else
            log_error "TYPO3 v14 web server: FAILED"
            all_healthy=false
        fi
        
        if curl -f -s "http://localhost:8014/typo3" > /dev/null; then
            log_success "TYPO3 v14 backend: OK"
        else
            log_error "TYPO3 v14 backend: FAILED"
            all_healthy=false
        fi
    fi
    
    if [ "$all_healthy" = true ]; then
        log_success "All environments are healthy"
        return 0
    else
        log_error "Some environments have issues"
        return 1
    fi
}

# Main execution
main() {
    local action=${1:-"help"}
    local version=${2:-"all"}
    local test_type=${3:-"all"}
    
    log_info "TYPO3 Multi-Version Test Runner"
    log_info "Action: $action, Version: $version, Test Type: $test_type"
    
    case $action in
        "setup")
            setup_complete_environment "$version"
            ;;
        "test")
            run_tests "$version" "$test_type"
            show_test_summary
            ;;
        "phpunit")
            run_phpunit_tests "$version"
            ;;
        "js"|"javascript")
            run_javascript_tests "$version"
            ;;
        "container"|"container-tests")
            run_container_tests "$version"
            ;;
        "integration")
            run_integration_tests "$version"
            ;;
        "status")
            show_status
            ;;
        "health")
            health_check "$version"
            ;;
        "cleanup"|"clean")
            cleanup_environments "$version"
            ;;
        "start")
            "$SCRIPT_DIR/setup-typo3-environment.sh" start "$version"
            ;;
        "stop")
            "$SCRIPT_DIR/setup-typo3-environment.sh" stop "$version"
            ;;
        *)
            echo "TYPO3 Multi-Version Test Runner"
            echo "==============================="
            echo ""
            echo "Usage: $0 {action} [version] [test_type]"
            echo ""
            echo "Actions:"
            echo "  setup      - Complete environment setup (Docker + Extension + Data)"
            echo "  test       - Run all tests"
            echo "  phpunit    - Run PHPUnit tests only"
            echo "  js         - Run JavaScript tests only"
            echo "  container  - Run container extension tests only"
            echo "  integration- Run integration tests only"
            echo "  start      - Start existing environments"
            echo "  stop       - Stop running environments"
            echo "  status     - Show environment status"
            echo "  health     - Run health check"
            echo "  cleanup    - Clean up all environments and data"
            echo ""
            echo "Versions:"
            echo "  13   - TYPO3 v13 only"
            echo "  14   - TYPO3 v14 only"
            echo "  all  - Both versions (default)"
            echo ""
            echo "Test Types (for 'test' action):"
            echo "  unit       - Unit tests only"
            echo "  js         - JavaScript tests only"
            echo "  integration- Integration tests only"
            echo "  all        - All test types (default)"
            echo ""
            echo "Examples:"
            echo "  $0 setup              # Setup both TYPO3 v13 and v14"
            echo "  $0 setup 13           # Setup TYPO3 v13 only"
            echo "  $0 test all unit      # Run unit tests on both versions"
            echo "  $0 test 14 js         # Run JavaScript tests for v14"
            echo "  $0 health             # Check if environments are healthy"
            echo "  $0 cleanup            # Clean up everything"
            exit 1
            ;;
    esac
}

# Execute main function with all arguments
main "$@"