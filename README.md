# WikiCube Knowledge Base for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/terpdev/cubewikipackage.svg?style=flat-square)](https://packagist.org/packages/terpdev/cubewikipackage)
[![Total Downloads](https://img.shields.io/packagist/dt/terpdev/cubewikipackage.svg?style=flat-square)](https://packagist.org/packages/terpdev/cubewikipackage)

A beautiful Filament plugin to display your WikiCube knowledge base content directly in your admin panel. Users can connect to different tenants using API tokens and browse their knowledge base with a clean, organized interface.

## Features

- 🔐 **Multi-tenant support** - Users can enter their own API tokens to switch between tenants
- 📚 **Beautiful UI** - Native Filament components for a seamless experience
- 🚀 **Smart caching** - Automatic caching of API responses for better performance
- 🔄 **Easy refresh** - Clear cache and reload data with one click
- 🎨 **Customizable** - Configure navigation groups, sort order, and more
- 💪 **Filament v4** - Built for the latest Filament version

## Requirements

- PHP 8.1+
- Laravel 11.28+
- Filament v4.0+

## Installation

Install the package via composer:

```bash
composer require terpdev/cubewikipackage
```

Publish the config file (optional):

```bash
php artisan vendor:publish --tag="cubewikipackage-config"
```

## Configuration

Add your WikiCube API settings to your `.env` file (optional - users can also enter tokens via the UI):

```env
WIKICUBE_API_URL=https://wikicube.test
WIKICUBE_API_TOKEN=your-api-token-here
WIKICUBE_CACHE_DURATION=5
```

## Usage

### Automatic Registration (Default)

The Knowledge Base page is automatically added to your admin panel sidebar after installation. No configuration needed!

### Manual Registration (Advanced)

If you want more control, you can manually register the plugin in your `AdminPanelProvider`:

```php
use TerpDev\CubeWikiPackage\Filament\CubeWikiPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->plugins([
            CubeWikiPlugin::make()
                ->navigationGroup('Content') // Custom navigation group
                ->navigationSort(10),        // Custom sort order
        ]);
}
```

### Plugin Options

```php
CubeWikiPlugin::make()
    ->navigationGroup('Knowledge Base')  // Set custom navigation group
    ->navigationSort(99)                 // Set navigation sort order
    ->withoutNavigationGroup()           // Remove navigation grouping
    ->enabled(true)                      // Enable/disable the plugin
    ->disabled()                         // Disable the plugin
```

## How It Works

1. **Install the package** - Users will see "WikiCube" in their sidebar
2. **Enter API token** - Click on WikiCube and enter a WikiCube API token
3. **View knowledge base** - Browse applications, categories, and pages
4. **Switch tenants** - Click "Change Token" to connect to a different tenant
5. **Refresh data** - Use the refresh button to clear cache and reload

## Screenshots

### Token Input
Users enter their WikiCube API token to connect to a tenant.

### Knowledge Base View
Beautiful, organized display of applications, categories, and pages with expandable content.

## Configuration File

The published config file `config/cubewikipackage.php`:

```php
return [
    /*
    | WikiCube API URL
    */
    'api_url' => env('WIKICUBE_API_URL', 'https://wikicube.test'),

    /*
    | API Token (optional - users can enter via UI)
    */
    'api_token' => env('WIKICUBE_API_TOKEN'),

    /*
    | Cache duration in minutes
    */
    'cache_duration' => env('WIKICUBE_CACHE_DURATION', 5),
];
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [TerpDev](https://github.com/terpdev)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

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

