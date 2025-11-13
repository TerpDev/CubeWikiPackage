<?php

namespace TerpDev\CubeWikiPackage;

use Filament\Facades\Filament;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Livewire;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TerpDev\CubeWikiPackage\Commands\CubeWikiPackageCommand;
use TerpDev\CubeWikiPackage\Livewire\DocumentationButton;
use TerpDev\CubeWikiPackage\Testing\TestsCubeWikiPackage;

class CubeWikiPackageServiceProvider extends PackageServiceProvider
{
    public static string $name = 'cubewikipackage';

    public static string $viewNamespace = 'cubewikipackage';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('terpdev/cubewikipackage');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        // Registreer het CubeWiki Filament Panel
        $this->app->register(\TerpDev\CubeWikiPackage\Filament\CubeWikiPanelProvider::class);
    }

    public function packageBooted(): void
    {
        // Register Livewire components
        Livewire::component('cubewikipackage-documentation-button', DocumentationButton::class);
        Livewire::component('cubewiki-sidebar', \TerpDev\CubeWikiPackage\Filament\Pages\Sidebar::class);

        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());
        $this->publishes([
            __DIR__.'/../resources/css/index.css' => public_path('vendor/cubewiki/index.css'),
        ], 'cubewiki-assets');

    }

    protected function getAssetPackageName(): ?string
    {
        return 'terpdev/cubewikipackage';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            Css::make('cubewikipackage-styles', __DIR__ . '/../resources/dist/cubewikipackage.css'),
            Js::make('cubewikipackage-scripts', __DIR__ . '/../resources/dist/cubewikipackage.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            CubeWikiPackageCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_cubewikipackage_table',
        ];
    }
}

