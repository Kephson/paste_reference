# TYPO3 Multi-Version Docker Test Environments

This directory contains Docker-based test environments for testing the
paste_reference extension across multiple TYPO3 versions.

## Overview

The test environments provides an isolated, reproducible setup for:
- TYPO3 v13.4 with PHP 8.2
- MariaDB database
- Pre-configured extension installation
- Test data seeding

## Directory Structure

```
Tests/Docker/
├── typo3-v13/                 # TYPO3 v13.4 environment
│   ├── Dockerfile             # Web server container
│   ├── docker-compose.yml     # Service orchestration
│   ├── composer.json          # TYPO3 v13 dependencies
│   ├── apache-vhost.conf      # Apache configuration
│   ├── LocalConfiguration.php # TYPO3 configuration
│   ├── additional.php         # Test-specific settings
│   └── init-db.sql           # Database initialization
└── README.md                  # This file
```

## Quick Start

### Prerequisites

- Docker and Docker Compose installed
- At least 4GB RAM available for containers
- Ports 8013, 8014, 3313, 3314 available

### Setup Complete Environment

```bash
# Setup
./Tests/Scripts/run-tests.sh setup
```

### Manual Setup

```bash
# Setup Docker environments
./Tests/Scripts/setup-typo3-environment.sh setup all

# Install extensions
./Tests/Scripts/install-extension.sh install all

# Seed test data
./Tests/Scripts/seed-test-data.sh seed all
```

## Environment Details

### TYPO3 v13 Environment

- **Web URL**: http://localhost:8013
- **Backend URL**: http://localhost:8013/typo3
- **Database**: localhost:3313
- **PHP Version**: 8.2
- **TYPO3 Version**: 13.4
- **Container Extension**: b13/container ^3.0

### Default Credentials

- **Admin User**: admin / password
- **Test Editor**: test_editor / password
- **Test Admin**: test_admin / password
- **Database**: typo3 / typo3

## Test Data

The environment includes comprehensive test data:

### Pages Structure
- Test Suite Root (UID: 200)
  - Basic Copy Test (UID: 201)
  - Container Test (UID: 202)
  - Multi-Column Test (UID: 203)
  - Nested Container Test (UID: 204)
  - Reference Chain Test (UID: 205)

### Content Elements
- Source elements for copy/paste testing
- Container elements with nested content
- Multi-column layouts
- Reference chains for advanced testing
- Media elements with file references

### Backend Users
- Test editor with limited permissions
- Test admin with full permissions
- Workspace configurations (if available)

## Usage Examples

### Environment Management

```bash
# Check status
./Tests/Scripts/run-tests.sh status

# Start environments
./Tests/Scripts/run-tests.sh start

# Stop environments
./Tests/Scripts/run-tests.sh stop

# Health check
./Tests/Scripts/run-tests.sh health

# Complete cleanup
./Tests/Scripts/run-tests.sh cleanup
```

### Running Tests

```bash
# Run all tests on both versions
./Tests/Scripts/run-tests.sh test

# Run specific test types
./Tests/Scripts/run-tests.sh test all phpunit
./Tests/Scripts/run-tests.sh test all js
./Tests/Scripts/run-tests.sh test all integration
```

### Manual Testing

Access the TYPO3 backends to manually test paste-reference functionality:

1. **TYPO3 v13**: http://localhost:8013/typo3

Navigate to the test pages and use the paste-reference functionality to verify
cross-version compatibility.

## Configuration

### Environment Variables

Each environment supports these environment variables:

- `TYPO3_VERSION`: Target TYPO3 version
- `PHP_VERSION`: PHP version to use
- `TYPO3_CONTEXT`: TYPO3 application context
- `TYPO3_TEST_DB_HOST`: Database host
- `TYPO3_TEST_DB_NAME`: Database name
- `TYPO3_TEST_DB_USER`: Database user
- `TYPO3_TEST_DB_PASSWORD`: Database password

### Customization

To customize the environments:

1. **Modify Dockerfile**: Add additional PHP extensions or system packages
2. **Update composer.json**: Change TYPO3 or extension versions
3. **Adjust LocalConfiguration.php**: Modify TYPO3 settings
4. **Edit additional.php**: Add test-specific configurations
5. **Update init-db.sql**: Modify initial database structure

### Volume Mounts

- Extension source code is mounted read-only from the project root
- Database data persists in named Docker volumes
- TYPO3 var directory persists for file uploads and caches
- Fileadmin directory persists for media files

## Troubleshooting

### Common Issues

1. **Port Conflicts**
   ```bash
   # Check if ports are in use
   netstat -tulpn | grep -E ':(8013|8014|3313|3314)'

   # Modify docker-compose.yml to use different ports
   ```

2. **Memory Issues**
   ```bash
   # Increase Docker memory limit
   # Check Docker Desktop settings or daemon configuration
   ```

3. **Permission Problems**
   ```bash
   # Fix file permissions
   docker exec typo3-v13_web_1 chown -R www-data:www-data /var/www/html
   ```

4. **Database Connection Issues**
   ```bash
   # Check database container logs
   docker logs typo3-v13_db_1

   # Restart database containers
   docker-compose -f Tests/Docker/typo3-v13/docker-compose.yml restart db
   ```

### Logs and Debugging

```bash
# View container logs
docker logs typo3-v13_web_1

# Access container shell
docker exec -it typo3-v13_web_1 bash

# Check TYPO3 logs
docker exec typo3-v13_web_1 tail -f var/log/typo3_test.log
```

### Performance Optimization

1. **Enable Docker BuildKit**
   ```bash
   export DOCKER_BUILDKIT=1
   ```

2. **Use Docker Layer Caching**
   ```bash
   docker-compose build --parallel
   ```

3. **Optimize Composer**
   ```bash
   # Use composer cache volume
   docker volume create composer_cache
   # Mount in docker-compose.yml: composer_cache:/root/.composer
   ```

## Integration with CI/CD

These Docker environments are designed to work with GitHub Actions and other CI/CD systems:

1. **Matrix Strategy**: Test against both TYPO3 versions in parallel
2. **Caching**: Docker layers and Composer dependencies can be cached
3. **Artifacts**: Test results and logs can be collected as artifacts
4. **Status Checks**: Health checks provide reliable status information

See the GitHub Actions workflow configuration for implementation details.

## Maintenance

### Updating TYPO3 Versions

1. Update `composer.json` files with new version constraints
2. Modify `Dockerfile` if PHP version changes are needed
3. Update `LocalConfiguration.php` for version-specific settings
4. Test the updated environments thoroughly

### Adding New TYPO3 Versions

1. Copy existing version directory (e.g., `typo3-v13` → `typo3-v14`)
2. Update all configuration files for the new version
3. Modify scripts to include the new version
4. Update documentation and CI configuration

### Security Updates

- Regularly update base Docker images
- Keep TYPO3 and extension dependencies current
- Review and update security configurations
- Monitor for security advisories

## Contributing

When contributing to the test environments:

1. Test changes against both TYPO3 versions
2. Update documentation for any configuration changes
3. Ensure backward compatibility where possible
4. Add appropriate test data for new features
5. Update scripts and automation as needed
