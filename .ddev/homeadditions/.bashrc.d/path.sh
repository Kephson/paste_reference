#!/bin/bash

# This detects if the composer binary folder has been reconfigured,
# which is a casual practice for TYPO3 extension development setups.
CHANGED_COMPOSER_FOLDER="$( jq -r '.config."bin-dir"' composer.json )"
if [[ -n "${CHANGED_COMPOSER_FOLDER}" ]]; then
    PATH="${PATH}:$(pwd)/${CHANGED_COMPOSER_FOLDER}"
fi

# Detect legacy installation binary path
if [[ -f "typo3/sysext/core/bin/typo3" ]]; then
    PATH="${PATH}:$(pwd)/typo3/sysext/core/bin"
fi

# Ensure to symlink additional.php ddev configuration to changed folder
if [[ -f "config/system/additional.php" ]]; then
    if [[ ! -f "${DDEV_DOCROOT}/../config/system/additional.php" ]]; then
        mkdir -p "${DDEV_DOCROOT}/../config/system"
        CURRENT_FOLDER="$( pwd )"
        cd "${DDEV_DOCROOT}/../config/system"
        [[ -f "../../../config/system/additional.php" ]] && ln -s ../../../config/system/additional.php && echo ">> additional.php symlink created"
        cd "${CURRENT_FOLDER}"
    fi
fi
