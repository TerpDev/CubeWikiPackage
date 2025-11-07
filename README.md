# This is my package cubewikipackage

[![Latest Version on Packagist](https://img.shields.io/packagist/v/terpdev/cubewikipackage.svg?style=flat-square)](https://packagist.org/packages/terpdev/cubewikipackage)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/terpdev/cubewikipackage/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/terpdev/cubewikipackage/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/terpdev/cubewikipackage/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/terpdev/cubewikipackage/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/terpdev/cubewikipackage.svg?style=flat-square)](https://packagist.org/packages/terpdev/cubewikipackage)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require terpdev/cubewikipackage
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="cubewikipackage-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="cubewikipackage-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="cubewikipackage-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$cubeWikiPackage = new TerpDev\CubeWikiPackage();
echo $cubeWikiPackage->echoPhrase('Hello, TerpDev!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [terpdev](https://github.com/TerpDev)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
