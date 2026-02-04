#!/bin/bash

# TYPO3 Extension Installation Script for Test Environments
# This script installs and configures the paste-reference extension in test environments

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
EXTENSION_KEY="paste_reference"

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

# Function to install extension in TYPO3 environment
install_extension() {
    local version=$1
    local container_name="typo3-v${version}_web_1"
    
    log_info "Installing paste-reference extension in TYPO3 v$version..."
    
    # Check if container is running
    if ! docker ps | grep -q "$container_name"; then
        log_error "TYPO3 v$version container is not running"
        return 1
    fi
    
    # Copy extension files to container
    log_info "Copying extension files to TYPO3 v$version container..."
    docker cp "$PROJECT_ROOT/." "$container_name:/var/www/html/extensions/$EXTENSION_KEY/"
    
    # Set proper permissions
    docker exec "$container_name" chown -R www-data:www-data "/var/www/html/extensions/$EXTENSION_KEY"
    
    # Create symlink in TYPO3 extensions directory
    docker exec "$container_name" ln -sf "/var/www/html/extensions/$EXTENSION_KEY" "/var/www/html/public/typo3conf/ext/$EXTENSION_KEY" || true
    
    # Activate extension via TYPO3 CLI
    log_info "Activating extension in TYPO3 v$version..."
    docker exec "$container_name" php vendor/bin/typo3 extension:activate "$EXTENSION_KEY" || {
        log_warning "Failed to activate extension via CLI, trying alternative method..."
        
        # Alternative: Direct database insertion
        docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "
            INSERT IGNORE INTO sys_extension (extkey, version, type, siteRelPath, lastUpdated, serializedDependencies, state) 
            VALUES ('$EXTENSION_KEY', '4.0.2', 'L', 'typo3conf/ext/$EXTENSION_KEY/', UNIX_TIMESTAMP(), '', 1);
        " || true
    }
    
    # Clear all caches
    log_info "Clearing caches in TYPO3 v$version..."
    docker exec "$container_name" php vendor/bin/typo3 cache:flush || true
    docker exec "$container_name" php vendor/bin/typo3 cache:warmup || true
    
    # Update database schema if needed
    log_info "Updating database schema in TYPO3 v$version..."
    docker exec "$container_name" php vendor/bin/typo3 database:updateschema || true
    
    log_success "Extension installation completed for TYPO3 v$version"
}

# Function to configure extension
configure_extension() {
    local version=$1
    local container_name="typo3-v${version}_web_1"
    
    log_info "Configuring paste-reference extension in TYPO3 v$version..."
    
    # Set extension configuration
    docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "
        INSERT INTO sys_registry (entry_namespace, entry_key, entry_value) 
        VALUES ('extensionDataStorage', '$EXTENSION_KEY', 'a:2:{s:11:\"enableDebug\";s:1:\"1\";s:13:\"enableLogging\";s:1:\"1\";}')
        ON DUPLICATE KEY UPDATE entry_value = VALUES(entry_value);
    " || true
    
    # Enable extension in backend modules
    docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "
        UPDATE be_users SET options = CONCAT(COALESCE(options, ''), ',moduleData[web_layout][showHidden]=1') WHERE uid = 1;
    " || true
    
    log_success "Extension configuration completed for TYPO3 v$version"
}

# Function to install container extension (dependency)
install_container_extension() {
    local version=$1
    local container_name="typo3-v${version}_web_1"
    
    log_info "Installing b13/container extension in TYPO3 v$version..."
    
    # Install via composer if not already installed
    docker exec "$container_name" composer require "b13/container:^2.3" || {
        if [ "$version" = "14" ]; then
            docker exec "$container_name" composer require "b13/container:^3.0" || true
        fi
    }
    
    # Activate container extension
    docker exec "$container_name" php vendor/bin/typo3 extension:activate container || true
    
    # Clear caches after container installation
    docker exec "$container_name" php vendor/bin/typo3 cache:flush || true
    
    log_success "Container extension installed for TYPO3 v$version"
}

# Function to create test content
create_test_content() {
    local version=$1
    local container_name="typo3-v${version}_web_1"
    
    log_info "Creating test content for TYPO3 v$version..."
    
    # Create test pages with container elements
    docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "
        -- Create test pages
        INSERT IGNORE INTO pages (uid, pid, title, doktype, tstamp, crdate, slug) VALUES 
        (100, 1, 'Paste Reference Test', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '/paste-reference-test'),
        (101, 100, 'Container Test Page', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '/paste-reference-test/container-test');
        
        -- Create test content elements
        INSERT IGNORE INTO tt_content (uid, pid, CType, header, bodytext, colPos, tstamp, crdate) VALUES 
        (100, 100, 'text', 'Source Element for Copy', 'This element will be copied as reference.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
        (101, 100, 'text', 'Another Source Element', 'Another element for testing multiple selections.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
        (102, 101, 'container_2cols', 'Test Container', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
        (103, 101, 'text', 'Element in Container Column 1', 'Content in first container column.', 101, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
        (104, 101, 'text', 'Element in Container Column 2', 'Content in second container column.', 102, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
        
        -- Update container parent relations
        UPDATE tt_content SET tx_container_parent = 102 WHERE uid IN (103, 104);
    " || true
    
    log_success "Test content created for TYPO3 v$version"
}

# Function to verify installation
verify_installation() {
    local version=$1
    local container_name="typo3-v${version}_web_1"
    
    log_info "Verifying installation for TYPO3 v$version..."
    
    # Check if extension is active
    local ext_active=$(docker exec "$container_name" php vendor/bin/typo3 extension:list | grep "$EXTENSION_KEY" | grep -c "active" || echo "0")
    
    if [ "$ext_active" -gt 0 ]; then
        log_success "Extension is active in TYPO3 v$version"
    else
        log_warning "Extension may not be properly activated in TYPO3 v$version"
    fi
    
    # Check if JavaScript files are accessible
    local web_port
    if [ "$version" = "13" ]; then
        web_port="8013"
    else
        web_port="8014"
    fi
    
    if curl -f -s "http://localhost:$web_port/typo3conf/ext/$EXTENSION_KEY/Resources/Public/JavaScript/paste-reference.js" > /dev/null; then
        log_success "Extension JavaScript files are accessible in TYPO3 v$version"
    else
        log_warning "Extension JavaScript files may not be accessible in TYPO3 v$version"
    fi
    
    # Check database tables
    local table_count=$(docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "SHOW TABLES LIKE 'tt_content';" | wc -l)
    
    if [ "$table_count" -gt 1 ]; then
        log_success "Database tables are available in TYPO3 v$version"
    else
        log_warning "Database tables may not be properly set up in TYPO3 v$version"
    fi
}

# Main execution
main() {
    local action=${1:-"install"}
    local version=${2:-"all"}
    
    log_info "TYPO3 Extension Installation Script"
    log_info "Action: $action, Version: $version"
    
    case $action in
        "install")
            if [ "$version" = "all" ] || [ "$version" = "13" ]; then
                install_container_extension "13"
                install_extension "13"
                configure_extension "13"
                create_test_content "13"
                verify_installation "13"
            fi
            
            if [ "$version" = "all" ] || [ "$version" = "14" ]; then
                install_container_extension "14"
                install_extension "14"
                configure_extension "14"
                create_test_content "14"
                verify_installation "14"
            fi
            ;;
        "verify")
            if [ "$version" = "all" ] || [ "$version" = "13" ]; then
                verify_installation "13"
            fi
            
            if [ "$version" = "all" ] || [ "$version" = "14" ]; then
                verify_installation "14"
            fi
            ;;
        *)
            echo "Usage: $0 {install|verify} [13|14|all]"
            echo ""
            echo "Actions:"
            echo "  install - Install and configure extension"
            echo "  verify  - Verify installation"
            echo ""
            echo "Versions:"
            echo "  13   - TYPO3 v13 only"
            echo "  14   - TYPO3 v14 only"
            echo "  all  - Both versions (default)"
            exit 1
            ;;
    esac
}

# Execute main function with all arguments
main "$@"