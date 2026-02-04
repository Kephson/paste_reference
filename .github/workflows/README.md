# GitHub Actions Workflows

This directory contains the CI/CD workflows for the TYPO3 paste-reference extension.

## Workflows

### ci.yml
Main CI workflow that runs on every push and pull request. Includes:
- Code quality checks (PHP linting, CGL, PHPStan)
- Documentation rendering
- Multi-version compatibility check integration

**Triggers:**
- Push to any branch
- Pull request events (opened, edited, reopened, synchronize, ready_for_review)
- Manual dispatch

### typo3-multi-version-tests.yml
Multi-version compatibility testing across TYPO3 v13 and v14 with different PHP versions.

**Triggers:**
- Push to main/develop branches (only when relevant files change)
- Pull request events (only when relevant files change)
- Manual dispatch with configurable parameters

**File Change Detection:**
- PHP files: `Classes/**`, `ext_emconf.php`, `ext_tables.php`, `composer.json`
- JavaScript files: `Resources/Public/JavaScript/**`, `package.json`
- Configuration files: `Configuration/**`, `ext_conf_template.txt`
- Test files: `Tests/**`, workflow files

**Conditional Execution:**
- JavaScript tests: Run when JS files or tests change
- PHP unit tests: Run when PHP files, config, or tests change
- Functional tests: Run when PHP files, config, or tests change
- Integration tests: Run when any relevant files change

### nightly-main.yml
Scheduled nightly runs on the main branch.

**Triggers:**
- Scheduled: Daily at 05:42 UTC
- Manual dispatch

**Behavior:**
- Runs full CI suite
- Runs complete multi-version tests (force_full_test=true)

### publish.yaml
Handles publishing to TYPO3 Extension Repository (TER).

**Triggers:**
- Push of version tags (format: x.y.z)

## Workflow Integration

The workflows are designed to work together:

1. **ci.yml** runs basic quality checks and integrates with multi-version testing
2. **typo3-multi-version-tests.yml** provides comprehensive compatibility testing
3. **nightly-main.yml** ensures regular full testing of the main branch
4. **publish.yaml** handles release automation

## Configuration

### Multi-Version Testing Matrix

Current matrix configuration:
- TYPO3 versions: 13.4, 14.1
- PHP versions: 8.2, 8.3, 8.4, 8.5
- Exclusions: 
  - TYPO3 v13.4 with PHP 8.4+ (not supported)
  - TYPO3 v14.1 with PHP 8.5 (compatibility pending verification)

### Manual Dispatch Parameters

The multi-version workflow supports manual execution with parameters:
- `typo3_versions`: Comma-separated list of TYPO3 versions to test
- `php_versions`: Comma-separated list of PHP versions to test  
- `force_full_test`: Boolean to force full test suite execution

### Status Checks

Required status checks for pull requests:
- Code quality checks (from ci.yml)
- Multi-version compatibility (from typo3-multi-version-tests.yml)

## Adding New TYPO3 Versions

To add support for a new TYPO3 version:

1. Update the matrix in `typo3-multi-version-tests.yml`
2. Create corresponding Docker environment in `Tests/Docker/typo3-vXX/`
3. Update exclusions if needed for PHP version compatibility
4. Test the new configuration manually before merging

## Troubleshooting

### Common Issues

1. **Docker build failures**: Check if base images are available and Dockerfile syntax
2. **Test timeouts**: Increase timeout values or optimize test execution
3. **Cache issues**: Clear caches or update cache keys
4. **Permission errors**: Ensure proper file permissions in Docker containers
5. **PHP version not supported**: If you get "Invalid option" errors for PHP versions, check:
   - The `Build/Scripts/runTests.sh` script supports the PHP version (update regex if needed)
   - TYPO3 core testing Docker images are available for that PHP version
   - TYPO3 version compatibility with the PHP version

### Debugging

- Use `workflow_dispatch` with specific parameters to test individual configurations
- Check workflow logs for detailed error messages
- Review test artifacts uploaded by failed runs
- Use the nightly workflow to test changes on main branch