# Test Sitepackage

A simple TYPO3 sitepackage for testing the paste-reference extension with container elements.

## Features

- 2-column backend layout (Main Content + Sidebar)
- Container element `ce_columns2` with two columns (colPos 101 and 102)
- Basic Fluid templates and CSS styling
- Integration with the container extension

## Installation

1. Place this extension in your TYPO3 installation
2. Activate the extension in the Extension Manager
3. Include the static TypoScript template "Test Sitepackage"
4. Create a page and set the backend layout to "Default Layout"

## Container Element

The `ce_columns2` container element provides:
- Two columns with colPos 101 (left) and 102 (right)
- Responsive layout using flexbox
- Integration with the container extension for proper backend editing

## Testing with paste-reference

This sitepackage is designed to test the paste-reference extension functionality with:
- Regular page columns (colPos 0 and 1)
- Container columns (colPos 101 and 102)
- Mixed environments with both types of columns

## File Structure

```
test_sitepackage/
├── Configuration/
│   ├── TCA/Overrides/
│   │   └── tt_content.php          # Container registration
│   ├── TsConfig/Page/
│   │   └── container.tsconfig      # Backend layout & container config
│   └── TypoScript/
│       ├── constants.typoscript
│       └── setup.typoscript        # Page & container rendering
├── Resources/
│   ├── Private/
│   │   ├── Layouts/
│   │   │   └── Default.html
│   │   └── Templates/
│   │       ├── ContentElements/
│   │       │   └── Columns2.html   # Container template
│   │       └── Page/
│   │           └── Default.html    # Page template
│   └── Public/
│       ├── Css/
│       │   └── theme.css           # Basic styling
│       └── Icons/
│           └── ce_columns2.svg     # Container icon
├── ext_emconf.php
├── ext_localconf.php
├── ext_tables.php
└── composer.json
```