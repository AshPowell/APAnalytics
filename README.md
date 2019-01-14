# APAnalytics Package

[![Build Status](https://travis-ci.org/ash-powell/apanalytics.svg?branch=master)](https://travis-ci.org/ash-powell/apanalytics)
[![styleci](https://styleci.io/repos/CHANGEME/shield)](https://styleci.io/repos/CHANGEME)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ash-powell/apanalytics/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ash-powell/apanalytics/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/CHANGEME/mini.png)](https://insight.sensiolabs.com/projects/CHANGEME)
[![Coverage Status](https://coveralls.io/repos/github/ash-powell/apanalytics/badge.svg?branch=master)](https://coveralls.io/github/ash-powell/apanalytics?branch=master)

[![Packagist](https://img.shields.io/packagist/v/ash-powell/apanalytics.svg)](https://packagist.org/packages/ash-powell/apanalytics)
[![Packagist](https://poser.pugx.org/ash-powell/apanalytics/d/total.svg)](https://packagist.org/packages/ash-powell/apanalytics)
[![Packagist](https://img.shields.io/packagist/l/ash-powell/apanalytics.svg)](https://packagist.org/packages/ash-powell/apanalytics)

Simple Logging and Viewing for Analytics using MongoDB - Very rough first draft

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
AshPowell\APAnalytics\APAnalyticsServiceProvider::class,
```

### Register Facade

Register package facade in `config/app.php` in `aliases` section
```php
AshPowell\APAnalytics\Facades\APAnalytics::class,
```

### Publish Configuration File

```bash
php artisan vendor:publish --provider="AshPowell\APAnalytics\APAnalyticsServiceProvider" --tag="config"
```

### Publish VueJS Analytic Chart Thing... Requires Vue-ApexCharts

```bash
php artisan vendor:publish --provider="AshPowell\APAnalytics\APAnalyticsServiceProvider" --tag="views"
```

## Usage

### Extend Analytic Models From Jessenger instead of Elequent
```php
use Jenssegers\Mongodb\Eloquent\Model;

class ViewAnalytic extends Model;
```

### Use Custom Trait to set correct db etc
```php
use Jenssegers\Mongodb\Eloquent\Model;
use AshPowell\APAnalytics\Traits\isAnalytic;

class ViewAnalytic extends Model
{
    use isAnalytic;
```

### To Log events simply use the built in helper as follows:
```php
trackEvent('collection', $items, $userId = null, $params = []),
```
- Collection will get plauralised
- Items can be models, collections, or custom (see config for model formatting)
- UserId is who performed the action, nullable, default is logged user
- Params is an array of extra config, nullable also

## Security

If you discover any security related issues, please email ash-powell@hotmail.co.uk
instead of using the issue tracker.

## Credits

- [Ash Powell](https://github.com/ash-powell/apanalytics)
- [All contributors](https://github.com/ash-powell/apanalytics/graphs/contributors)

This package relies heavily on
[jenssegers/laravel-mongodb](https://github.com/jenssegers/laravel-mongodb).
