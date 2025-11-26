<?php

namespace TerpDev\CubeWikiPackage;

use Filament\Facades\Filament;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TerpDev\CubeWikiPackage\Actions\Panel\Components\HelpAction as PanelHelpAction;
use TerpDev\CubeWikiPackage\Commands\CubeWikiPackageCommand;
use TerpDev\CubeWikiPackage\Filament\CubeWikiPanelProvider;
use TerpDev\CubeWikiPackage\Filament\Pages\Sidebar;
use TerpDev\CubeWikiPackage\Livewire\BackToPanelButton;
use TerpDev\CubeWikiPackage\Livewire\DocumentationButton;

class CubeWikiPackageServiceProvider extends PackageServiceProvider
{
    public static string $name = 'cubewikipackage';

    public static string $cubeWikiPanelPath = 'cubewiki';

    public static string $viewNamespace = 'cubewikipackage';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command): void {
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
        $this->app->register(CubeWikiPanelProvider::class);
        $this->mergeConfigFrom(__DIR__.'/../config/cubewikipackage.php', 'cubewikipackage');

    }

    public function packageBooted(): void
    {
        Livewire::component('cubewikipackage-helpaction', PanelHelpAction::class);
        Livewire::component('cubewikipackage-documentation-button', DocumentationButton::class);
        Livewire::component('cubewikipackage-back-to-panel-button', BackToPanelButton::class);
        Livewire::component('cubewiki-sidebar', Sidebar::class);

        $this->ensureCubeWikiSessionDefaults();
        $this->publishes([
            __DIR__.'/../config/cubewikipackage.php' => config_path('cubewikipackage.php'),
        ], 'cubewikipackage-config');
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            function (): string {
                $currentPanel = Filament::getCurrentPanel();

                if ($currentPanel?->getId() === self::$cubeWikiPanelPath) {
                    return Blade::render('<livewire:cubewikipackage-back-to-panel-button />');
                }

                if ($currentPanel) {
                    return Blade::render('<livewire:cubewikipackage-documentation-button />');
                }

                return '';
            }
        );
        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        FilamentIcon::register($this->getIcons());
    }

    protected function ensureCubeWikiSessionDefaults(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $token = session('cubewiki_token');

        if (! $token) {
            $token = config('cubewikipackage.api_token');

            if ($token) {
                session(['cubewiki_token' => $token]);
            }
        }
        $appName = session('cubewiki_application_name');
        if (! $appName) {
            $appName = config('cubewikipackage.default_application');

            if ($appName) {
                session(['cubewiki_application_name' => $appName]);
            }
        }
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
            Css::make('cubewikipackage-styles', __DIR__.'/../resources/dist/cubewikipackage.css'),
            Js::make('cubewikipackage-scripts', __DIR__.'/../resources/dist/cubewikipackage.js'),
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
