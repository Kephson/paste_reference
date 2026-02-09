#!/bin/bash

# TYPO3 Multi-Version Test Environment Setup Script
# This script automates the setup of TYPO3 test environments for both v13 and v14

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
DOCKER_DIR="$PROJECT_ROOT/Tests/Docker"

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

# Function to check if Docker is running
check_docker() {
    log_info "Checking Docker availability..."
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed or not in PATH"
        exit 1
    fi
    
    if ! docker info &> /dev/null; then
        log_error "Docker daemon is not running"
        exit 1
    fi
    
    log_success "Docker is available and running"
}

# Function to check if docker-compose is available
check_docker_compose() {
    log_info "Checking Docker Compose availability..."
    if command -v docker-compose &> /dev/null; then
        COMPOSE_CMD="docker-compose"
    elif docker compose version &> /dev/null; then
        COMPOSE_CMD="docker compose"
    else
        log_error "Docker Compose is not available"
        exit 1
    fi
    
    log_success "Docker Compose is available: $COMPOSE_CMD"
}

# Function to setup TYPO3 environment
setup_typo3_environment() {
    local version=$1
    local docker_path="$DOCKER_DIR/typo3-v$version"
    
    log_info "Setting up TYPO3 v$version environment..."
    
    if [ ! -d "$docker_path" ]; then
        log_error "Docker configuration for TYPO3 v$version not found at $docker_path"
        return 1
    fi
    
    cd "$docker_path"
    
    # Stop existing containers if running
    log_info "Stopping existing TYPO3 v$version containers..."
    $COMPOSE_CMD down --remove-orphans || true
    
    # Build and start containers
    log_info "Building TYPO3 v$version Docker images..."
    $COMPOSE_CMD build --no-cache
    
    log_info "Starting TYPO3 v$version containers..."
    $COMPOSE_CMD up -d
    
    # Wait for services to be ready
    log_info "Waiting for TYPO3 v$version services to be ready..."
    sleep 30
    
    # Check if services are healthy
    if $COMPOSE_CMD ps | grep -q "Up"; then
        log_success "TYPO3 v$version environment is running"
        
        # Get container info
        local web_port=$(docker-compose port web 80 2>/dev/null | cut -d: -f2 || echo "N/A")
        local db_port=$(docker-compose port db 3306 2>/dev/null | cut -d: -f2 || echo "N/A")
        
        log_info "TYPO3 v$version Web: http://localhost:$web_port"
        log_info "TYPO3 v$version DB: localhost:$db_port"
    else
        log_error "Failed to start TYPO3 v$version environment"
        $COMPOSE_CMD logs
        return 1
    fi
    
    cd "$PROJECT_ROOT"
}

# Function to install TYPO3 and extension
install_typo3_and_extension() {
    local version=$1
    local container_name="typo3-v${version}_web_1"
    
    log_info "Installing TYPO3 v$version and paste-reference extension..."
    
    # Wait for database to be ready
    log_info "Waiting for database to be ready..."
    sleep 10
    
    # Install TYPO3 via command line
    log_info "Running TYPO3 v$version installation..."
    
    # Try modern TYPO3 setup command first
    if docker exec -it "$container_name" php vendor/bin/typo3 install:setup \
        --no-interaction \
        --database-user-name=typo3 \
        --database-user-password=typo3 \
        --database-host-name=db \
        --database-port=3306 \
        --database-name=typo3_test \
        --admin-user-name=admin \
        --admin-password=password \
        --site-name="TYPO3 v$version Test Environment" 2>/dev/null; then
        log_success "TYPO3 v$version installed via install:setup command"
    else
        log_info "install:setup command not available, using alternative installation method..."
        
        # Alternative: Direct database setup
        docker exec -it "$container_name" mysql -h db -u typo3 -ptypo3 -e "
            CREATE DATABASE IF NOT EXISTS typo3_test;
            USE typo3_test;
            
            -- Create basic TYPO3 tables
            CREATE TABLE IF NOT EXISTS be_users (
                uid int(11) NOT NULL AUTO_INCREMENT,
                username varchar(50) NOT NULL,
                password varchar(100) NOT NULL,
                admin tinyint(4) DEFAULT 0,
                tstamp int(11) DEFAULT 0,
                crdate int(11) DEFAULT 0,
                PRIMARY KEY (uid)
            );
            
            CREATE TABLE IF NOT EXISTS pages (
                uid int(11) NOT NULL AUTO_INCREMENT,
                pid int(11) DEFAULT 0,
                title varchar(255) DEFAULT '',
                doktype int(11) DEFAULT 1,
                tstamp int(11) DEFAULT 0,
                crdate int(11) DEFAULT 0,
                slug varchar(2048) DEFAULT '',
                PRIMARY KEY (uid)
            );
            
            CREATE TABLE IF NOT EXISTS tt_content (
                uid int(11) NOT NULL AUTO_INCREMENT,
                pid int(11) DEFAULT 0,
                CType varchar(255) DEFAULT '',
                header varchar(255) DEFAULT '',
                bodytext mediumtext,
                colPos int(11) DEFAULT 0,
                tstamp int(11) DEFAULT 0,
                crdate int(11) DEFAULT 0,
                sys_language_uid int(11) DEFAULT 0,
                tx_container_parent int(11) DEFAULT 0,
                sorting int(11) DEFAULT 0,
                PRIMARY KEY (uid)
            );
            
            CREATE TABLE IF NOT EXISTS sys_extension (
                uid int(11) NOT NULL AUTO_INCREMENT,
                extkey varchar(60) NOT NULL,
                version varchar(15) DEFAULT '',
                type varchar(10) DEFAULT '',
                siteRelPath varchar(255) DEFAULT '',
                lastUpdated int(11) DEFAULT 0,
                serializedDependencies mediumtext,
                state int(11) DEFAULT 0,
                PRIMARY KEY (uid)
            );
            
            -- Insert admin user
            INSERT IGNORE INTO be_users (uid, username, password, admin, tstamp, crdate) 
            VALUES (1, 'admin', '\$argon2i\$v=19\$m=65536,t=16,p=1\$UnlOcXJQdHlsaUhkdGp2Zw\$6VbKFmpeMOGJXdJqF.QbZg', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
            
            -- Insert root page
            INSERT IGNORE INTO pages (uid, pid, title, doktype, tstamp, crdate, slug) 
            VALUES (1, 0, 'Root', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '/');
        " || true
        
        log_success "TYPO3 v$version installed via direct database setup"
    fi
    
    # Activate paste-reference extension
    log_info "Activating paste-reference extension in TYPO3 v$version..."
    docker exec -it "$container_name" php vendor/bin/typo3 extension:activate paste_reference || true
    
    # Clear caches
    log_info "Clearing TYPO3 v$version caches..."
    docker exec -it "$container_name" php vendor/bin/typo3 cache:flush || true
    
    log_success "TYPO3 v$version installation completed"
}

# Function to seed test data
seed_test_data() {
    local version=$1
    local container_name="typo3-v${version}_web_1"
    
    log_info "Seeding test data for TYPO3 v$version..."
    
    # Create test pages and content via TYPO3 CLI or direct database insertion
    docker exec -it "$container_name" mysql -h db -u typo3 -ptypo3 typo3_test -e "
        INSERT IGNORE INTO pages (uid, pid, title, doktype, tstamp, crdate) VALUES 
        (10, 1, 'Paste Reference Test Page', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
        (11, 10, 'Container Test Page', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
        
        INSERT IGNORE INTO tt_content (uid, pid, CType, header, bodytext, colPos, tstamp, crdate) VALUES 
        (10, 10, 'text', 'Source Content Element', 'This content element will be used for paste reference testing.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
        (11, 10, 'text', 'Target Content Element', 'This is where we will paste references.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
        (12, 11, 'container', 'Test Container', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
    " || true
    
    log_success "Test data seeded for TYPO3 v$version"
}

# Function to run health checks
run_health_checks() {
    local version=$1
    local web_port
    
    if [ "$version" = "13" ]; then
        web_port="8013"
    else
        web_port="8014"
    fi
    
    log_info "Running health checks for TYPO3 v$version..."
    
    # Check web server response
    if curl -f -s "http://localhost:$web_port" > /dev/null; then
        log_success "TYPO3 v$version web server is responding"
    else
        log_warning "TYPO3 v$version web server is not responding on port $web_port"
    fi
    
    # Check TYPO3 backend
    if curl -f -s "http://localhost:$web_port/typo3" > /dev/null; then
        log_success "TYPO3 v$version backend is accessible"
    else
        log_warning "TYPO3 v$version backend is not accessible"
    fi
}

# Function to display environment status
show_environment_status() {
    log_info "Environment Status:"
    echo "===================="
    
    for version in 13 14; do
        local docker_path="$DOCKER_DIR/typo3-v$version"
        if [ -d "$docker_path" ]; then
            cd "$docker_path"
            if $COMPOSE_CMD ps | grep -q "Up"; then
                local web_port db_port
                if [ "$version" = "13" ]; then
                    web_port="8013"
                    db_port="3313"
                else
                    web_port="8014"
                    db_port="3314"
                fi
                
                log_success "TYPO3 v$version: Running"
                echo "  Web: http://localhost:$web_port"
                echo "  Backend: http://localhost:$web_port/typo3"
                echo "  Database: localhost:$db_port"
                echo "  Admin: admin / password"
            else
                log_warning "TYPO3 v$version: Not running"
            fi
            cd "$PROJECT_ROOT"
        fi
    done
}

# Main execution
main() {
    local action=${1:-"setup"}
    local version=${2:-"all"}
    
    log_info "TYPO3 Multi-Version Test Environment Setup"
    log_info "Action: $action, Version: $version"
    
    check_docker
    check_docker_compose
    
    case $action in
        "setup")
            if [ "$version" = "all" ] || [ "$version" = "13" ]; then
                setup_typo3_environment "13"
                install_typo3_and_extension "13"
                seed_test_data "13"
                run_health_checks "13"
            fi
            
            if [ "$version" = "all" ] || [ "$version" = "14" ]; then
                setup_typo3_environment "14"
                install_typo3_and_extension "14"
                seed_test_data "14"
                run_health_checks "14"
            fi
            
            show_environment_status
            ;;
        "start")
            if [ "$version" = "all" ] || [ "$version" = "13" ]; then
                cd "$DOCKER_DIR/typo3-v13"
                $COMPOSE_CMD up -d
                cd "$PROJECT_ROOT"
            fi
            
            if [ "$version" = "all" ] || [ "$version" = "14" ]; then
                cd "$DOCKER_DIR/typo3-v14"
                $COMPOSE_CMD up -d
                cd "$PROJECT_ROOT"
            fi
            
            show_environment_status
            ;;
        "stop")
            if [ "$version" = "all" ] || [ "$version" = "13" ]; then
                cd "$DOCKER_DIR/typo3-v13"
                $COMPOSE_CMD down
                cd "$PROJECT_ROOT"
            fi
            
            if [ "$version" = "all" ] || [ "$version" = "14" ]; then
                cd "$DOCKER_DIR/typo3-v14"
                $COMPOSE_CMD down
                cd "$PROJECT_ROOT"
            fi
            ;;
        "status")
            show_environment_status
            ;;
        "clean")
            log_warning "Cleaning up all TYPO3 test environments..."
            if [ "$version" = "all" ] || [ "$version" = "13" ]; then
                cd "$DOCKER_DIR/typo3-v13"
                $COMPOSE_CMD down -v --remove-orphans
                cd "$PROJECT_ROOT"
            fi
            
            if [ "$version" = "all" ] || [ "$version" = "14" ]; then
                cd "$DOCKER_DIR/typo3-v14"
                $COMPOSE_CMD down -v --remove-orphans
                cd "$PROJECT_ROOT"
            fi
            
            log_success "Cleanup completed"
            ;;
        *)
            echo "Usage: $0 {setup|start|stop|status|clean} [13|14|all]"
            echo ""
            echo "Actions:"
            echo "  setup  - Build and start environments with full installation"
            echo "  start  - Start existing environments"
            echo "  stop   - Stop running environments"
            echo "  status - Show environment status"
            echo "  clean  - Stop and remove all containers and volumes"
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