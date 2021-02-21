# TYPO3 Extension `paste_reference`

[![Latest Stable Version](https://poser.pugx.org/ehaerer/paste-reference/v)](//packagist.org/packages/ehaerer/paste-reference)
[![Total Downloads](https://poser.pugx.org/ehaerer/paste-reference/downloads)](//packagist.org/packages/ehaerer/paste-reference) 
[![Latest Unstable Version](https://poser.pugx.org/ehaerer/paste-reference/v/unstable)](//packagist.org/packages/ehaerer/paste-reference) 
[![License](https://poser.pugx.org/ehaerer/paste-reference/license)](//packagist.org/packages/ehaerer/paste-reference)

> This extension brings the extracted functions from gridelements to copy and paste content elements also as reference and not only as copy. 
> A lot of TYPO3 users love these features but don't know that this aren't core features.

## 1 Features

* Copy content elements and paste as reference (also in context menu with right click)
* Copy content elements from other pages in page module
* [Full documentation in TYPO3 TER][1]

## 2 Usage

### 2.1 Installation

#### Installation using Composer

The recommended way to install the extension is using [Composer][2].

Run the following command within your Composer based TYPO3 project:

```
composer require ehaerer/paste-reference
```

#### Installation as extension from TYPO3 Extension Repository (TER) - not recommended

Download and install the [extension][3] with the extension manager module.

### 2.2 Minimal setup

1) Just install the extension and you are done

## 3 Report issues

Please report issue directly in the [issue tracker in the Github repository][6].

## 4 Administration corner

### 4.1 Settings in extension configuration

* **disableCopyFromPageButton** - You can disable the "copy from page button" in the page module if you don't need it.

### 4.2 Changelog

Please look into the [official extension documentation in changelog chapter][4].

### 4.3 Release Management

Paste reference uses [**semantic versioning**][5], which means, that
* **bugfix updates** (e.g. 1.0.0 => 1.0.1) just includes small bugfixes or security relevant stuff without breaking changes,
* **minor updates** (e.g. 1.0.0 => 1.1.0) includes new features and smaller tasks without breaking changes,
* and **major updates** (e.g. 1.0.0 => 2.0.0) breaking changes wich can be refactorings, features or bugfixes.

### 4.4 Contribution

**Pull Requests** are gladly welcome! Nevertheless please don't forget to add an issue and connect it to your pull requests. This
is very helpful to understand what kind of issue the **PR** is going to solve.

Bugfixes: Please describe what kind of bug your fix solve and give us feedback how to reproduce the issue. We're going
to accept only bugfixes if we can reproduce the issue.

Features: Not every feature is relevant for the bulk of `paste_reference` users. In addition: We don't want to make ``paste_reference``
even more complicated in usability for an edge case feature. It helps to have a discussion about a new feature before you open a pull request.


[1]: https://docs.typo3.org/p/ehaerer/paste-reference/master/en-us/
[2]: https://getcomposer.org/
[3]: https://extensions.typo3.org/extension/paste_reference
[4]: https://docs.typo3.org/p/ehaerer/paste-reference/master/en-us/Misc/Changelog/Index.html
[5]: https://semver.org/
[6]: https://github.com/Kephson/paste_reference/issues
