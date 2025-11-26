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

    public static array $importantPages = [];

    public function getId(): string
    {
        return 'cubewiki-plugin';
    }

    public function importantPages(array $pages): static
    {
        self::$importantPages = $pages;

        return $this;
    }

    public static function getImportantPages(): array
    {
        return self::$importantPages;
    }

    public function register(Panel $panel): void
    {
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

    public static function make(): self
    {
        return new self;
    }
}
