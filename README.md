# APAnalytics Package

[![Build Status](https://travis-ci.org/ash-powell/apanalytics.svg?branch=master)](https://travis-ci.org/ash-powell/apanalytics)
[![styleci](https://styleci.io/repos/CHANGEME/shield)](https://styleci.io/repos/CHANGEME)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ash-powell/apanalytics/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ash-powell/apanalytics/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/CHANGEME/mini.png)](https://insight.sensiolabs.com/projects/CHANGEME)
[![Coverage Status](https://coveralls.io/repos/github/ash-powell/apanalytics/badge.svg?branch=master)](https://coveralls.io/github/ash-powell/apanalytics?branch=master)

[![Packagist](https://img.shields.io/packagist/v/ash-powell/apanalytics.svg)](https://packagist.org/packages/ash-powell/apanalytics)
[![Packagist](https://poser.pugx.org/ash-powell/apanalytics/d/total.svg)](https://packagist.org/packages/ash-powell/apanalytics)
[![Packagist](https://img.shields.io/packagist/l/ash-powell/apanalytics.svg)](https://packagist.org/packages/ash-powell/apanalytics)

Package description: CHANGE ME

## Installation

Install via composer
```bash
composer require ash-powell/apanalytics
```

### Register Service Provider

**Note! This and next step are optional if you use laravel>=5.5 with package
auto discovery feature.**

Add service provider to `config/app.php` in `providers` section
```php
AshPowell\APAnalytics\ServiceProvider::class,
```

### Register Facade

Register package facade in `config/app.php` in `aliases` section
```php
AshPowell\APAnalytics\Facades\APAnalytics::class,
```

### Publish Configuration File

```bash
php artisan vendor:publish --provider="AshPowell\APAnalytics\ServiceProvider" --tag="config"
```

## Usage

CHANGE ME

## Security

If you discover any security related issues, please email ash-powell@hotmail.co.uk
instead of using the issue tracker.

## Credits

- [Ash Powell](https://github.com/ash-powell/apanalytics)
- [All contributors](https://github.com/ash-powell/apanalytics/graphs/contributors)

This package is bootstrapped with the help of
[melihovv/laravel-package-generator](https://github.com/melihovv/laravel-package-generator).
