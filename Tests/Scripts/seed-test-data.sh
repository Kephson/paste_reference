#!/bin/bash

# TYPO3 Test Data Seeding Script
# This script creates comprehensive test data for paste-reference extension testing

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

# Function to seed basic test data
seed_basic_data() {
    local version=$1
    local container_name="typo3-v${version}_web_1"
    
    log_info "Seeding basic test data for TYPO3 v$version..."
    
    docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "
        -- Create comprehensive page structure
        INSERT IGNORE INTO pages (uid, pid, title, doktype, tstamp, crdate, slug, hidden) VALUES 
        (200, 1, 'Test Suite Root', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '/test-suite', 0),
        (201, 200, 'Basic Copy Test', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '/test-suite/basic-copy', 0),
        (202, 200, 'Container Test', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '/test-suite/container', 0),
        (203, 200, 'Multi-Column Test', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '/test-suite/multi-column', 0),
        (204, 200, 'Nested Container Test', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '/test-suite/nested', 0),
        (205, 200, 'Reference Chain Test', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '/test-suite/reference-chain', 0);
        
        -- Create backend user groups for testing
        INSERT IGNORE INTO be_groups (uid, title, description, tstamp, crdate) VALUES 
        (10, 'Test Editors', 'Group for testing paste-reference functionality', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
        (11, 'Test Admins', 'Admin group for comprehensive testing', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
        
        -- Create additional backend users for testing
        INSERT IGNORE INTO be_users (uid, username, password, admin, usergroup, tstamp, crdate, realName, email) VALUES 
        (10, 'test_editor', '\$argon2i\$v=19\$m=65536,t=16,p=1\$UnlOcXhBbEFJUWNHWnVOZg\$4QWLmPOYhYnx0hdGt4/DbhzFtJF/Z8/xJqKwGqmn4pY', 0, '10', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'Test Editor', 'editor@test.local'),
        (11, 'test_admin', '\$argon2i\$v=19\$m=65536,t=16,p=1\$UnlOcXhBbEFJUWNHWnVOZg\$4QWLmPOYhYnx0hdGt4/DbhzFtJF/Z8/xJqKwGqmn4pY', 1, '11', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'Test Admin', 'admin@test.local');
    " || true
    
    log_success "Basic test data seeded for TYPO3 v$version"
}

# Function to seed content elements for basic testing
seed_content_elements() {
    local version=$1
    local container_name="typo3-v${version}_web_1"
    
    log_info "Seeding content elements for TYPO3 v$version..."
    
    docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "
        -- Basic content elements for copy/paste testing
        INSERT IGNORE INTO tt_content (uid, pid, CType, header, bodytext, colPos, tstamp, crdate, sorting) VALUES 
        -- Basic Copy Test Page (201)
        (200, 201, 'text', 'Source Text Element', 'This is a source text element that will be copied as reference. It contains <strong>HTML formatting</strong> and <em>styled content</em>.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256),
        (201, 201, 'header', 'Source Header Element', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 512),
        (202, 201, 'image', 'Source Image Element', 'Image element with caption for testing.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 768),
        (203, 201, 'text', 'Target Area - Column 0', 'This column will receive pasted references.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1024),
        (204, 201, 'text', 'Target Area - Column 1', 'This is the right column for paste testing.', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256),
        
        -- Multi-Column Test Page (203)
        (210, 203, 'text', 'Multi-Col Source 1', 'First source element for multi-column testing.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256),
        (211, 203, 'text', 'Multi-Col Source 2', 'Second source element for multi-column testing.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 512),
        (212, 203, 'text', 'Multi-Col Source 3', 'Third source element for multi-column testing.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 768),
        (213, 203, 'text', 'Left Column Target', 'Target area in left column.', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256),
        (214, 203, 'text', 'Right Column Target', 'Target area in right column.', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256);
    " || true
    
    log_success "Content elements seeded for TYPO3 v$version"
}

# Function to seed container elements
seed_container_elements() {
    local version=$1
    local container_name="typo3-v${version}_web_1"
    
    log_info "Seeding container elements for TYPO3 v$version..."
    
    # Determine container CType based on version
    local container_ctype="container_2cols"
    if [ "$version" = "14" ]; then
        container_ctype="container_twocols"
    fi
    
    docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "
        -- Container Test Page (202)
        INSERT IGNORE INTO tt_content (uid, pid, CType, header, bodytext, colPos, tstamp, crdate, sorting, tx_container_parent) VALUES 
        -- Main container
        (220, 202, '$container_ctype', 'Test Container Element', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256, 0),
        
        -- Elements inside container
        (221, 202, 'text', 'Container Col 1 - Element 1', 'First element in container column 1.', 101, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256, 220),
        (222, 202, 'text', 'Container Col 1 - Element 2', 'Second element in container column 1.', 101, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 512, 220),
        (223, 202, 'text', 'Container Col 2 - Element 1', 'First element in container column 2.', 102, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256, 220),
        (224, 202, 'text', 'Container Col 2 - Element 2', 'Second element in container column 2.', 102, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 512, 220),
        
        -- Source elements outside container
        (225, 202, 'text', 'Source for Container Copy', 'This element will be copied into containers.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1024, 0),
        (226, 202, 'header', 'Another Source Element', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1280, 0),
        
        -- Nested Container Test Page (204)
        (230, 204, '$container_ctype', 'Parent Container', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256, 0),
        (231, 204, '$container_ctype', 'Nested Container', '', 101, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256, 230),
        (232, 204, 'text', 'Nested Element 1', 'Element in nested container column 1.', 101, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256, 231),
        (233, 204, 'text', 'Nested Element 2', 'Element in nested container column 2.', 102, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256, 231),
        (234, 204, 'text', 'Parent Container Element', 'Element in parent container column 2.', 102, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 512, 230);
    " || true
    
    log_success "Container elements seeded for TYPO3 v$version"
}

# Function to seed reference chain test data
seed_reference_chain() {
    local version=$1
    local container_name="typo3-v${version}_web_1"
    
    log_info "Seeding reference chain test data for TYPO3 v$version..."
    
    docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "
        -- Reference Chain Test Page (205)
        INSERT IGNORE INTO tt_content (uid, pid, CType, header, bodytext, colPos, tstamp, crdate, sorting) VALUES 
        -- Original element
        (240, 205, 'text', 'Original Element', 'This is the original element that will be referenced multiple times.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256),
        
        -- First level references (will be created by paste-reference functionality)
        (241, 205, 'text', 'Reference Level 1A', 'Placeholder for first reference.', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256),
        (242, 205, 'text', 'Reference Level 1B', 'Placeholder for second reference.', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 512),
        
        -- Second level references (references to references)
        (243, 205, 'text', 'Reference Level 2A', 'Placeholder for reference to reference.', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256),
        (244, 205, 'text', 'Reference Level 2B', 'Placeholder for another reference to reference.', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 512);
    " || true
    
    log_success "Reference chain test data seeded for TYPO3 v$version"
}

# Function to seed file references and media
seed_media_elements() {
    local version=$1
    local container_name="typo3-v${version}_web_1"
    
    log_info "Seeding media elements for TYPO3 v$version..."
    
    docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "
        -- Create file storage record
        INSERT IGNORE INTO sys_file_storage (uid, pid, tstamp, crdate, name, description, driver, configuration, is_default, is_browsable, is_public, is_writable, is_online) VALUES 
        (1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'fileadmin/ (auto-created)', '', 'Local', 'a:3:{s:7:\"basePath\";s:10:\"fileadmin/\";s:7:\"pathType\";s:8:\"relative\";s:8:\"caseSensitive\";s:4:\"true\";}', 1, 1, 1, 1, 1);
        
        -- Create test files
        INSERT IGNORE INTO sys_file (uid, pid, tstamp, last_indexed, missing, storage, type, metadata, identifier, identifier_hash, folder_hash, extension, mime_type, name, sha1, size, creation_date, modification_date) VALUES 
        (1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 1, 2, 0, '/test-image.jpg', SHA1('/test-image.jpg'), SHA1('/'), 'jpg', 'image/jpeg', 'test-image.jpg', SHA1('test-content'), 12345, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
        (2, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 1, 3, 0, '/test-document.pdf', SHA1('/test-document.pdf'), SHA1('/'), 'pdf', 'application/pdf', 'test-document.pdf', SHA1('test-pdf-content'), 54321, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
        
        -- Create file references for content elements
        INSERT IGNORE INTO sys_file_reference (uid, pid, tstamp, crdate, uid_local, uid_foreign, tablenames, fieldname, sorting_foreign, table_local, title, description, alternative, link, crop) VALUES 
        (1, 202, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1, 202, 'tt_content', 'image', 1, 'sys_file', 'Test Image', 'Test image for paste-reference testing', 'Test Alt Text', '', ''),
        (2, 202, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 2, 202, 'tt_content', 'media', 1, 'sys_file', 'Test Document', 'Test document for media testing', '', '', '');
    " || true
    
    log_success "Media elements seeded for TYPO3 v$version"
}

# Function to create test workspace (if workspaces extension is available)
seed_workspace_data() {
    local version=$1
    local container_name="typo3-v${version}_web_1"
    
    log_info "Seeding workspace test data for TYPO3 v$version..."
    
    docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "
        -- Create test workspace
        INSERT IGNORE INTO sys_workspace (uid, pid, tstamp, crdate, title, description, adminusers, members, reviewers, publish_access, stagechg_notification, edit_notification_defaults, edit_allow_notificaton_settings, publish_notification_defaults, publish_allow_notificaton_settings, db_mountpoints, file_mountpoints, publish_time, unpublish_time, freeze, live_edit, vtypes, disable, starttime, endtime, custom_stages, notification_defaults, notification_preselection, stagechg_notification_preselection, allow_notificaton_settings, notification_mode) VALUES 
        (1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'Test Workspace', 'Workspace for testing paste-reference functionality', '1,11', '10,11', '11', 1, 0, 0, 0, 0, 0, '', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        
        -- Create workspace versions of some content elements
        INSERT IGNORE INTO tt_content (uid, pid, CType, header, bodytext, colPos, tstamp, crdate, sorting, t3ver_oid, t3ver_wsid, t3ver_state, t3ver_stage) VALUES 
        (250, 201, 'text', 'Workspace Modified Element', 'This element has been modified in workspace.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256, 200, 1, 0, 0),
        (251, 201, 'text', 'New Workspace Element', 'This element was created in workspace.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1280, 0, 1, 1, 0);
    " || true
    
    log_success "Workspace test data seeded for TYPO3 v$version"
}

# Function to verify seeded data
verify_seeded_data() {
    local version=$1
    local container_name="typo3-v${version}_web_1"
    
    log_info "Verifying seeded data for TYPO3 v$version..."
    
    # Count pages
    local page_count=$(docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "SELECT COUNT(*) FROM pages WHERE pid = 200;" | tail -n 1)
    log_info "Created $page_count test pages"
    
    # Count content elements
    local content_count=$(docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "SELECT COUNT(*) FROM tt_content WHERE uid >= 200;" | tail -n 1)
    log_info "Created $content_count test content elements"
    
    # Count backend users
    local user_count=$(docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "SELECT COUNT(*) FROM be_users WHERE uid >= 10;" | tail -n 1)
    log_info "Created $user_count test backend users"
    
    # Check container elements
    local container_count=$(docker exec "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "SELECT COUNT(*) FROM tt_content WHERE CType LIKE 'container%' AND uid >= 200;" | tail -n 1)
    log_info "Created $container_count container elements"
    
    log_success "Data verification completed for TYPO3 v$version"
}

# Main execution
main() {
    local action=${1:-"seed"}
    local version=${2:-"all"}
    
    log_info "TYPO3 Test Data Seeding Script"
    log_info "Action: $action, Version: $version"
    
    case $action in
        "seed")
            if [ "$version" = "all" ] || [ "$version" = "13" ]; then
                seed_basic_data "13"
                seed_content_elements "13"
                seed_container_elements "13"
                seed_reference_chain "13"
                seed_media_elements "13"
                seed_workspace_data "13"
                verify_seeded_data "13"
            fi
            
            if [ "$version" = "all" ] || [ "$version" = "14" ]; then
                seed_basic_data "14"
                seed_content_elements "14"
                seed_container_elements "14"
                seed_reference_chain "14"
                seed_media_elements "14"
                seed_workspace_data "14"
                verify_seeded_data "14"
            fi
            ;;
        "verify")
            if [ "$version" = "all" ] || [ "$version" = "13" ]; then
                verify_seeded_data "13"
            fi
            
            if [ "$version" = "all" ] || [ "$version" = "14" ]; then
                verify_seeded_data "14"
            fi
            ;;
        "clean")
            log_warning "Cleaning test data..."
            if [ "$version" = "all" ] || [ "$version" = "13" ]; then
                docker exec "typo3-v13_web_1" mysql -h db -u typo3 -ptypo3 typo3_test -e "
                    DELETE FROM tt_content WHERE uid >= 200;
                    DELETE FROM pages WHERE uid >= 200;
                    DELETE FROM be_users WHERE uid >= 10;
                    DELETE FROM be_groups WHERE uid >= 10;
                    DELETE FROM sys_file WHERE uid >= 1;
                    DELETE FROM sys_file_reference WHERE uid >= 1;
                    DELETE FROM sys_workspace WHERE uid >= 1;
                " || true
            fi
            
            if [ "$version" = "all" ] || [ "$version" = "14" ]; then
                docker exec "typo3-v14_web_1" mysql -h db -u typo3 -ptypo3 typo3_test -e "
                    DELETE FROM tt_content WHERE uid >= 200;
                    DELETE FROM pages WHERE uid >= 200;
                    DELETE FROM be_users WHERE uid >= 10;
                    DELETE FROM be_groups WHERE uid >= 10;
                    DELETE FROM sys_file WHERE uid >= 1;
                    DELETE FROM sys_file_reference WHERE uid >= 1;
                    DELETE FROM sys_workspace WHERE uid >= 1;
                " || true
            fi
            
            log_success "Test data cleaned"
            ;;
        *)
            echo "Usage: $0 {seed|verify|clean} [13|14|all]"
            echo ""
            echo "Actions:"
            echo "  seed   - Create comprehensive test data"
            echo "  verify - Verify existing test data"
            echo "  clean  - Remove all test data"
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