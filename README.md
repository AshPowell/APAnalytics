# APAnalytics Package

[![Build Status](https://travis-ci.org/AshPowell/APAnalytics.svg?branch=master)](https://travis-ci.org/AshPowell/APAnalytics)
[![styleci](https://styleci.io/repos/165663557/shield)](https://styleci.io/repos/165663557)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/AshPowell/APAnalytics/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/AshPowell/APAnalytics/?branch=master)

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

### Extend Analytic Models our Model instead of Elequent
#### This will make sure the correct db connection and table are used
```php
use AshPowell\APAnalytics\AnalyticModel as Model;

class ViewAnalytic extends Model;
```

### To Log events simply use the built in helper as follows:
```php
trackEvent('table', $items, $userId = null, $params = []),
```
- Table will get plauralised
- Items can be models, collections, or custom (see config for model formatting)
- UserId is who performed the action, nullable, default is logged user
- Params is an array of extra config, nullable also

## Security

If you discover any security related issues, please email ash-powell@hotmail.co.uk
instead of using the issue tracker.

## Credits

This package relies heavily on
[jenssegers/laravel-mongodb](https://github.com/jenssegers/laravel-mongodb).
