*UnifiedArchive* - an archive manager with unified interface for different formats.
Supports all basic (listing, reading, extracting and creation) and specific features (compression level, password-protection). 
Bundled with console program for working with archives.

UnifiedArchive is aimed to provide all operations on popular archive formats (zip, tar, rar, 7z) by all means and at
better performance (depends on your OS/PHP configuration). Also, it allows to work with less popular archive types
(with lack of functions).

[![Latest Stable Version](https://poser.pugx.org/wapmorgan/unified-archive/v/stable)](https://packagist.org/packages/wapmorgan/unified-archive)
[![Total Downloads](https://poser.pugx.org/wapmorgan/unified-archive/downloads)](https://packagist.org/packages/wapmorgan/unified-archive)
[![Daily Downloads](https://poser.pugx.org/wapmorgan/unified-archive/d/daily)](https://packagist.org/packages/wapmorgan/unified-archive)
[![License](https://poser.pugx.org/wapmorgan/unified-archive/license)](https://packagist.org/packages/wapmorgan/unified-archive)
[![Latest Unstable Version](https://poser.pugx.org/wapmorgan/unified-archive/v/unstable)](https://packagist.org/packages/wapmorgan/unified-archive)

Tests & Quality: [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/wapmorgan/UnifiedArchive/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/wapmorgan/UnifiedArchive/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/wapmorgan/UnifiedArchive/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/wapmorgan/UnifiedArchive/?branch=master)

1. [Goal](#goal)
2. [Functions & Features](#functions--features)
3. [Quick start](#quick-start)
4. [Built-in console archive manager](#built-in-console-archive-manager)
5. [Usage](docs/Usage.md)
6. [Drivers & formats](docs/Drivers.md)
7. [Full API description](docs/API.md)
8. [Changelog](CHANGELOG.md)

## Goal
Main purpose of this library is to create unified interface for popular archive formats, that works in
various system configurations via different drivers, when there is no ability to update system configuration, when code
delivered to another system configurations, or when you just want to install libraries and work.

UnifiedArchive introduces term "driver", which can be one of types:
1. PHP Extensions
2. Utilities + bridge
3. Pure PHP

By default, UA goes top-down to select first available driver for passed archive. So, PHP Extension driver will be used (if available), then Utilities + bridge driver (if available), and then Pure PHP driver.

There is at least one driver in all three types, which handles **zip** format, so this format can be fully supported in any OS/PHP configuration:
1. zip / phar PHP extensions
2. Utility + bridge SevenZip / AlchemyZippy
3. Pure NelexaZip

**tar** format (with compressed variants) supported by:
1. phar PHP extension
2. Utility + bridge SevenZip / AlchemyZippy
3. Pure TarByPear

**So, there is always one driver that supports popular formats, and you should not remember how to work with this concrete
driver (zip/phar/SevenZip/AlchemyZippy/NelexaZip), interface for them is uniform.**

## Functions & Features
Main functions:
- Open an archive with automatic format detection (more 20 formats).
- List archive content, calculate original size of archive
- Get details (original size, date of modification) of every archived file. Extract or stream archived file content
- Extract all archive content. Append an archive with new files or directories. Remove files from archive
- Create new archives with files/directories
- Fully implemented [PclZip-like interface for archives](docs/API.md#UnifiedArchive--getPclZipInterface) for all formats. Easy transition from old PclZip.

Specific functions:
- Read (zip, rar) & set (zip) archive comment
- [Open archives encrypted with password (zip, rar, 7z)](docs/API.md#UnifiedArchive--open)
- Set passwords (7z, zip) for new archives
- [Adjust compression level (zip, gzip, 7zip)](docs/API.md#UnifiedArchive--archiveFiles) for new archives

## Quick start
1. Installation and configuration
```shell
# 1. Install library
composer require wapmorgan/unified-archive
# Check supported formats with installed drivers
./vendor/bin/cam system:formats
# Check supported functions for zip format
./vendor/bin/cam system:format zip

# 2. Install new driver
# Read installation instructions from
./vendor/bin/cam system:drivers
# install missing drivers, for example pear/archive_tar
composer require pear/archive_tar
# check out driver functions
./vendor/bin/cam system:formats TarByPear

# if needed, install extensions, cli tools and php libraries
# to enable support of other formats
```

2. Usage
```php
use \wapmorgan\UnifiedArchive\UnifiedArchive;

# Extraction
$archive = UnifiedArchive::open('archive.zip'); // archive.rar, archive.tar.bz2

if ($archive !== null) {
    $output_dir = '/var/www/extracted';
    if (disk_free_space($output_dir) > $archive->getOriginalSize()) {
        $extracted = $archive->extractFiles($output_dir);
        echo 'Extracted files: ' . $extracted.PHP_EOL;
    }
}

# Archiving
UnifiedArchive::archiveFiles([
    'README.md' => '/default/path/to/README.md',
    '' => '/folder/with/content/',
], 'archive.zip');
```

## Built-in console archive manager
UnifiedArchive is distributed with a unified console program to manipulate archives.
It supports all formats and all operations on them that UnifiedArchive does, so it can be used to manipulate
archives without other system software. To show help, launch it:
```shell
./vendor/bin/cam list # help
./vendor/bin/cam archive:info archive.tar.gz # archive information
./vendor/bin/cam files:list -l archive.tar.gz # files list (or files:table for table)
./vendor/bin/cam archive:extract archive.tar.gz ./OUTPUT/ # extraction all archive
./vendor/bin/cam archive:create --compression=maximum archive2.tar.gz ./OUTPUT/partners # archive creation from files
```
