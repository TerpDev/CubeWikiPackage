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

    public function getId(): string
    {
        return 'cubewiki-plugin'; // mag anders heten dan panel-id, is alleen een unieke plugin-id
    }

    public function register(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            function (): string {
                // 👉 Huidige panel ophalen
                $currentPanel = Filament::getCurrentPanel();

                // Als we in het CubeWiki-panel zitten: NIETS renderen
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
