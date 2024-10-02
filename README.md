# TYPO3 Extension `paste_reference`

[![Latest Stable Version](https://poser.pugx.org/ehaerer/paste-reference/v)](//packagist.org/packages/ehaerer/paste-reference)
[![Latest Unstable Version](https://poser.pugx.org/ehaerer/paste-reference/v/unstable)](//packagist.org/packages/ehaerer/paste-reference)
[![License](https://poser.pugx.org/ehaerer/paste-reference/license)](//packagist.org/packages/ehaerer/paste-reference)
[![Total Downloads](https://poser.pugx.org/ehaerer/paste-reference/downloads)](//packagist.org/packages/ehaerer/paste-reference)
[![Monthly Downloads](https://poser.pugx.org/ehaerer/paste-reference/d/monthly)](//packagist.org/packages/ehaerer/paste-reference)
[![CI - main](https://github.com/Kephson/paste_reference/actions/workflows/ci.yml/badge.svg)](https://github.com/Kephson/paste_reference/actions/workflows/ci.yml)

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

## 5 Local development

### 5.1 Overview

This repository contains a so-called [Extension for the TYPO3 CMS](https://github.com/typo3) which cannot be used on its
own but has been prepared to install required dependency to provide a TYPO3 v12 composer based installation within the
untracked `.Build/` folder with `.Build/public/` being the doc-root to point a web-server on.

For simpler onboarding a generic [ddev project configuration]() is included to quickstart a local TYPO3 v12 instance
in a predefined environment along with data set. See [5.2](#52-use-ddev-to-setup-a-local-development-instance) for how
to use ddev.

### 5.2 Use ddev to setup a local development instance

> Please ensure to have the pre-requisit ddev and docker/colima/... installed and working to follow this section.

#### 5.2.1 Single command start-up

```bash
ddev start \
  && ddev composer install \
  && ddev restart \
  && ddev typo3 setup \
        --driver=mysqli \
        --host=db \
        --port=3306 \
        --dbname=db \
        --username=db \
        --password=db \
        --admin-username=john-doe \
        --admin-user-password='John-Doe-1701D.' \
        --admin-email="john.doe@example.com" \
        --project-name='ext-paste-reference' \
        --no-interaction \
        --server-type=apache \
        --force \
  && ddev restart \
  && ddev typo3 cache:warmup \
  && ddev typo3 styleguide:generate --create all \
  && ddev typo3 cache:warmup \
  && ddev launch /typo3/
```

which creates a instance with two different hidden page trees and a admin user without asking for it.
Adjust the `--admin-*` arguments to match your needs.

#### 5.2.2 Splittet startup commands

**First startup and composer package installation**

```bash
ddev start \
  && ddev composer install \
  && ddev restart
```

**Setup TYPO3 using typo3 setup command**

> Note that the following command is interactive and asks for admin user credential, name and email.
> Ensure to remember the values you enter here for later login into the TYPO3 backend.

```bash
ddev typo3 setup \
    --driver=mysqli \
    --host=db \
    --port=3306 \
    --dbname=db \
    --username=db \
    --password=db \
    --server-type=apache \
    --force \
  && ddev restart
```

**Use `EXT:styleguide` to create page trees**

```bash
ddev typo3 styleguide:generate --create all \
  && ddev typo3 cache:warmup
```

**Launch the backend login form**

```bash
ddev launch /typo3/
```

#### 5.2.3 Stop & destroy ddev instance

**Simply stop ddev instance**

```bash
ddev stop
```

**Completely remove ddev instance**

```bash
ddev stop -ROU
```

[1]: https://docs.typo3.org/p/ehaerer/paste-reference/master/en-us/
[2]: https://getcomposer.org/
[3]: https://extensions.typo3.org/extension/paste_reference
[4]: https://docs.typo3.org/p/ehaerer/paste-reference/master/en-us/Misc/Changelog/Index.html
[5]: https://semver.org/
[6]: https://github.com/Kephson/paste_reference/issues
