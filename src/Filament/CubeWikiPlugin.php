<?php

namespace TerpDev\CubeWikiPackage\Filament;

use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class CubeWikiPlugin implements Plugin
{
    public static string $cubeWikiPanelPath = 'cubewiki';
    public static string $buttonLabel = 'Documentation';
    public static string $buttonIcon = 'heroicon-o-book-open';

    // Important pages registry that demo or app can set


    public function getId(): string
    {
        return 'cubewiki-plugin';
    }

    public function register(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            function (): string {
                $currentPanel = Filament::getCurrentPanel();

                if ($currentPanel?->getId() === self::$cubeWikiPanelPath) {
                    return '';
                }

                return Blade::render('<livewire:cubewikipackage-documentation-button />');
            }
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            function (): string {
                $currentPanel = Filament::getCurrentPanel();

                if ($currentPanel?->getId() === self::$cubeWikiPanelPath) {
                    return '';
                }

                // Pass the registered important pages into the helpaction view via the component's view data
                // The HelpactionButton Livewire component will read CubeWikiPlugin::getImportantPages() itself,
                // but keeping this here allows later change if we want to render with Blade data.
                return Blade::render('<livewire:cubewikipackage-helpaction />');
            }
        );

    }
    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return new static();
    }
}
