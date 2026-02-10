# TYPO3 Extension `paste_reference`

[![Latest Stable Version](https://poser.pugx.org/ehaerer/paste-reference/v)](//packagist.org/packages/ehaerer/paste-reference)
[![Latest Unstable Version](https://poser.pugx.org/ehaerer/paste-reference/v/unstable)](//packagist.org/packages/ehaerer/paste-reference)
[![License](https://poser.pugx.org/ehaerer/paste-reference/license)](//packagist.org/packages/ehaerer/paste-reference)
[![Total Downloads](https://poser.pugx.org/ehaerer/paste-reference/downloads)](//packagist.org/packages/ehaerer/paste-reference)
[![Monthly Downloads](https://poser.pugx.org/ehaerer/paste-reference/d/monthly)](//packagist.org/packages/ehaerer/paste-reference)
[![CI - main](https://github.com/Kephson/paste_reference/actions/workflows/ci.yml/badge.svg)](https://github.com/Kephson/paste_reference/actions/workflows/ci.yml)

> This extension brings the extracted functions from gridelements to copy and paste
> content elements also as reference and not only as copy. A lot of TYPO3 users love
> these features but don't know that this aren't core features.

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

* **bugfix updates** (e.g. 1.0.0 => 1.0.1) just includes small bug-fixes or security relevant stuff without breaking changes,
* **minor updates** (e.g. 1.0.0 => 1.1.0) includes new features and smaller tasks without breaking changes,
* **major updates** (e.g. 1.0.0 => 2.0.0) breaking changes which can be refactoring, features or bug-fixes.

#### 4.3.1 Releases

* for TYPO3 v13: [4.0.3](https://github.com/Kephson/paste_reference/releases/tag/4.0.3)
* for TYPO3 v12: [3.0.5](https://github.com/Kephson/paste_reference/releases/tag/3.0.5)
* for TYPO3 v11: [2.0.5](https://github.com/Kephson/paste_reference/releases/tag/2.0.5)
* for TYPO3 v10: [1.0.3](https://github.com/Kephson/paste_reference/releases/tag/1.0.3)

To get the most recent development for the branch 4, consider installing the branch instead
of the release. The branch [v4-dev](https://github.com/Kephson/paste_reference/tree/v4-dev)
supports also TYPO3 v14 and includes unreleased bug-fixes.

#### 4.3.2 Branches

The following branches are of interest:

* for TYPO3 v13 and v14: [v4-dev](https://github.com/Kephson/paste_reference/tree/v4-dev)
+ for TYPO3 v12: [TYPO3_12](https://github.com/Kephson/paste_reference/tree/TYPO3_12)
* for TYPO3 v11: [TYPO3_11-5](https://github.com/Kephson/paste_reference/tree/TYPO3_11-5)
* for TYPO3 v10: [TYPO3_10-4](https://github.com/Kephson/paste_reference/tree/TYPO3_10-4)

Note, that releases are usually to prefer, if possible.
The branch [v4-dev](https://github.com/Kephson/paste_reference/tree/v4-dev) is ahead of the releases currently,
so in this case using the branch is better and gives you most recent development changes.

### 4.4 Contribution

**Pull Requests** are gladly welcome! Nevertheless please don't forget to add an issue and connect it to your pull requests. This
is very helpful to understand what kind of issue the **PR** is going to solve.

**Bugfixes:** Please describe what kind of bug your fix solve and give us feedback how to reproduce the issue. We're going
to accept only bugfixes if we can reproduce the issue.

**Issue Reports:** Some aspects of the extension might not work like intended or expected, other things might need an update,
or documentation or translations seem improvable. If you can't create an own pull request (PR) you can create just an issue only,
describing the faulty behavior or problem and proposing a better solution perhaps.

**Features:** Not every feature is relevant for the bulk of `paste_reference` users. In addition: We don't want to make ``paste_reference``
even more complicated in usability for an edge case feature. It helps to have a discussion about a new feature before you open a pull request.

**Financial support**
Development takes time and your financial support can enable developers to take the required time.
Even small donations are a nice way to say "thank you for the development!".
If you need invoices for the donations, please reach out to the according developers.

Currently the following active developers seek support:

- David Bruchmann, mail: david.bruchmann@gmail.com [![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/L3L81RC51J)

## 5 Local development

### 5.1 Overview

This repository contains a so-called Extension for the [TYPO3 CMS](https://github.com/typo3) which cannot be used on its
own but has been prepared to install required dependency to provide a TYPO3 composer based installation within the
untracked `.Build/` folder with `.Build/public/` being the doc-root to point a web-server on.

For simpler onboarding a generic [ddev project configuration]() is included to quickstart a local TYPO3 instance
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

#### 5.2.2 Startup commands step by step

**First startup and composer package installation**

```bash
ddev start \
  && ddev composer install \
  && ddev restart
```

**Setup TYPO3 using typo3 setup command**

**1) Using individual credentials**

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

**2) Using default credentials**

For local development there exist default credentials, which are also
applied above in *`5.2.1 Single command start-up`*. If you want to use
them you can execute the code-block below.

Note that you could use also individual values for the according
variables, starting with `--admin-` and the `--project-name`.
The other variables shouldn't be changed, if you don't know exactly
what you do. You still had the benefit of non-interactive setup.

```bash
ddev typo3 setup \
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

### 5.3 Render documentation

To render the documentation, the TYPO3 Documentation render-guides image can be used,
which is included in the `Build/Scripts/runTests.sh` dispatcher script.

**Render documentation**

```bash
Build/Scripts/runTests.sh -s renderDocumentation
```

**Open rendered documentation (Linux>**

```bash
Build/Scripts/runTests.sh -s renderDocumentation
xdg-open "Documentation-GENERATED-temp/Index.html"
```

**Open rendered documentation (MacOS)**

```bash
Build/Scripts/runTests.sh -s renderDocumentation
open "Documentation-GENERATED-temp/Index.html"
```

**Open rendered documentation (Windows)**

```bash
Build/Scripts/runTests.sh -s renderDocumentation
start "Documentation-GENERATED-temp/Index.html"
```


[1]: https://docs.typo3.org/p/ehaerer/paste-reference/master/en-us/
[2]: https://getcomposer.org/
[3]: https://extensions.typo3.org/extension/paste_reference
[4]: https://docs.typo3.org/p/ehaerer/paste-reference/master/en-us/Misc/Changelog/Index.html
[5]: https://semver.org/
[6]: https://github.com/Kephson/paste_reference/issues
